<?php

namespace App\Infrastructure\Jobs;

use App\Domain\AI\Contracts\EmbeddingServiceInterface;
use App\Domain\AI\ValueObjects\TickerEmbeddingData;
use App\Infrastructure\Persistence\Models\AtivoEmbedding;
use App\Infrastructure\Persistence\Models\Cotacao;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateAtivoEmbeddingsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(
        public readonly ?string $dataReferencia = null,
    ) {
        $this->queue = 'embeddings';
    }

    public function handle(EmbeddingServiceInterface $embeddingService): void
    {
        // Get all distinct tickers from cotacoes
        $query = Cotacao::where('tipo_mercado', 'padrao')
            ->select('ticker')
            ->distinct();

        if ($this->dataReferencia) {
            $query->where('data_pregao', $this->dataReferencia);
        }

        $tickers = $query->pluck('ticker')->toArray();

        if (empty($tickers)) {
            Log::info('UpdateAtivoEmbeddingsJob: No tickers found.');

            return;
        }

        Log::info('UpdateAtivoEmbeddingsJob: Processing '.count($tickers).' tickers.');

        // Process in chunks of 20
        $chunks = array_chunk($tickers, 20);

        foreach ($chunks as $chunk) {
            $texts = [];
            $tickerDataMap = [];

            foreach ($chunk as $ticker) {
                $data = $this->buildTickerEmbeddingData($ticker);
                if ($data !== null) {
                    $texts[] = $data->toEmbeddingText();
                    $tickerDataMap[] = $data;
                }
            }

            if (empty($texts)) {
                continue;
            }

            try {
                $embeddings = $embeddingService->embedBatch($texts);

                foreach ($embeddings as $index => $vector) {
                    if (! isset($tickerDataMap[$index])) {
                        continue;
                    }

                    $data = $tickerDataMap[$index];
                    $vectorStr = '['.implode(',', $vector).']';

                    AtivoEmbedding::updateOrCreate(
                        [
                            'ticker' => $data->ticker,
                            'data_referencia' => $data->dataReferencia,
                        ],
                        [
                            'embedding' => $vectorStr,
                            'metadata' => [
                                'preco_fechamento' => $data->precoFechamento,
                                'variacao_5d' => $data->variacao5d,
                                'variacao_20d' => $data->variacao20d,
                                'volatilidade_20d' => $data->volatilidade20d,
                                'volume' => $data->volume,
                            ],
                        ],
                    );
                }
            } catch (\Throwable $e) {
                Log::error('UpdateAtivoEmbeddingsJob: Chunk failed: '.$e->getMessage());
            }
        }

        Log::info('UpdateAtivoEmbeddingsJob: Completed.');
    }

    private function buildTickerEmbeddingData(string $ticker): ?TickerEmbeddingData
    {
        $cotacoes = Cotacao::where('ticker', $ticker)
            ->where('tipo_mercado', 'padrao')
            ->orderBy('data_pregao', 'desc')
            ->limit(20)
            ->get();

        if ($cotacoes->isEmpty()) {
            return null;
        }

        $latest = $cotacoes->first();
        $prices = $cotacoes->pluck('preco_fechamento')->map(fn ($p) => (float) $p)->toArray();

        $variacao5d = count($prices) >= 5 && $prices[4] > 0
            ? (($prices[0] - $prices[4]) / $prices[4]) * 100
            : 0.0;

        $variacao20d = count($prices) >= 20 && $prices[19] > 0
            ? (($prices[0] - $prices[19]) / $prices[19]) * 100
            : 0.0;

        $returns = [];
        for ($i = 0; $i < count($prices) - 1; $i++) {
            if ($prices[$i + 1] > 0) {
                $returns[] = ($prices[$i] - $prices[$i + 1]) / $prices[$i + 1];
            }
        }

        $volatilidade20d = 0.0;
        if (count($returns) >= 2) {
            $mean = array_sum($returns) / count($returns);
            $variance = array_sum(array_map(fn ($v) => ($v - $mean) ** 2, $returns)) / (count($returns) - 1);
            $volatilidade20d = sqrt($variance) * 100;
        }

        $avgVolume = $cotacoes->avg('volume');

        return new TickerEmbeddingData(
            ticker: $ticker,
            precoFechamento: (float) $latest->preco_fechamento,
            precoAbertura: (float) $latest->preco_abertura,
            precoMaximo: (float) $latest->preco_maximo,
            precoMinimo: (float) $latest->preco_minimo,
            volume: (float) $avgVolume,
            variacao5d: round($variacao5d, 2),
            variacao20d: round($variacao20d, 2),
            volatilidade20d: round($volatilidade20d, 2),
            dataReferencia: $latest->data_pregao->format('Y-m-d'),
        );
    }
}
