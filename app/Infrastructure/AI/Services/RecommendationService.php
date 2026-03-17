<?php

declare(strict_types=1);

namespace App\Infrastructure\AI\Services;

use App\Domain\AI\Contracts\EmbeddingServiceInterface;
use App\Domain\AI\Contracts\RecommendationServiceInterface;
use App\Domain\AI\ValueObjects\RecommendationResult;
use App\Domain\AI\ValueObjects\TickerEmbeddingData;
use App\Domain\Basket\Repositories\CestaRepositoryInterface;
use App\Domain\MarketData\Repositories\CotacaoRepositoryInterface;
use App\Infrastructure\AI\AiConfigResolver;
use App\Infrastructure\Persistence\Models\AtivoEmbedding;
use App\Infrastructure\Persistence\Models\Cotacao;
use Laravel\Ai\AnonymousAgent;

final class RecommendationService implements RecommendationServiceInterface
{
    public function __construct(
        private readonly EmbeddingServiceInterface $embeddingService,
        private readonly CestaRepositoryInterface $cestaRepo,
        private readonly CotacaoRepositoryInterface $cotacaoRepo,
        private readonly AiConfigResolver $configResolver,
    ) {}

    public function recommendForCesta(string $cestaId, int $limit = 5): RecommendationResult
    {
        $cesta = $this->cestaRepo->findById($cestaId);

        if ($cesta === null) {
            $cesta = $this->cestaRepo->findAtiva();
        }

        if ($cesta === null) {
            return new RecommendationResult(
                suggestedTickers: [],
                currentBasketSummary: [],
                confidence: 0.0,
                generatedAt: new \DateTimeImmutable,
            );
        }

        $tickers = $cesta->tickers();
        $percentuais = [];
        foreach ($tickers as $ticker) {
            $percentuais[$ticker] = $cesta->percentualPorTicker($ticker) ?? 0.0;
        }

        // Build current basket summary
        $currentBasketSummary = [];
        foreach ($percentuais as $ticker => $percentual) {
            $currentBasketSummary[] = [
                'ticker' => $ticker,
                'percentual' => $percentual,
            ];
        }

        // Try embedding-based recommendation first
        $compositeEmbedding = $this->buildCompositeEmbedding($tickers, $percentuais);

        if ($compositeEmbedding !== null) {
            $candidates = AtivoEmbedding::similarTo($compositeEmbedding, $limit + 5, $tickers);

            if ($candidates->isNotEmpty()) {
                $candidateTickers = $candidates->pluck('ticker')->toArray();
                $cotacoes = $this->cotacaoRepo->findLatestByTickers($candidateTickers);
                $cotacaoMap = [];
                foreach ($cotacoes as $cotacao) {
                    $cotacaoMap[$cotacao->ticker()] = $cotacao;
                }

                $candidateInfo = [];
                foreach ($candidates->take($limit) as $candidate) {
                    $ticker = $candidate->ticker;
                    $cotacao = $cotacaoMap[$ticker] ?? null;
                    $candidateInfo[] = [
                        'ticker' => $ticker,
                        'distance' => round((float) $candidate->distance, 4),
                        'preco_fechamento' => $cotacao?->precoFechamento(),
                        'volume' => $cotacao?->volume(),
                        'metadata' => $candidate->metadata,
                    ];
                }

                return $this->generateRecommendation($candidateInfo, $currentBasketSummary, $limit);
            }
        }

        // Fallback: use LLM directly without embeddings
        return $this->generateDirectLlmRecommendation($currentBasketSummary, $limit);
    }

    /**
     * Build a weighted composite embedding from basket tickers.
     *
     * @param  string[]  $tickers
     * @param  array<string, float>  $percentuais
     * @return float[]|null
     */
    private function buildCompositeEmbedding(array $tickers, array $percentuais): ?array
    {
        $embeddings = [];

        foreach ($tickers as $ticker) {
            $existing = AtivoEmbedding::byTicker($ticker)
                ->orderBy('data_referencia', 'desc')
                ->first();

            if ($existing && $existing->embedding) {
                // Parse pgvector string format [1.0,2.0,...] to array
                $vector = $this->parseVectorString($existing->embedding);
                if ($vector !== null) {
                    $embeddings[$ticker] = $vector;
                }
            } else {
                // Generate on-the-fly
                $embeddingData = $this->buildTickerEmbeddingData($ticker);
                if ($embeddingData !== null) {
                    $vector = $this->embeddingService->embed($embeddingData->toEmbeddingText());
                    $embeddings[$ticker] = $vector;
                }
            }
        }

        if (empty($embeddings)) {
            return null;
        }

        // Weighted average: sum(embedding_i * percentual_i / 100)
        $dimensions = count(reset($embeddings));
        $composite = array_fill(0, $dimensions, 0.0);

        foreach ($embeddings as $ticker => $vector) {
            $weight = ($percentuais[$ticker] ?? 20.0) / 100.0;
            for ($i = 0; $i < $dimensions; $i++) {
                $composite[$i] += $vector[$i] * $weight;
            }
        }

        return $composite;
    }

    /**
     * Build TickerEmbeddingData from cotacoes for a given ticker.
     */
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

        // Calculate variations
        $variacao5d = count($prices) >= 5
            ? (($prices[0] - $prices[4]) / $prices[4]) * 100
            : 0.0;

        $variacao20d = count($prices) >= 20
            ? (($prices[0] - $prices[19]) / $prices[19]) * 100
            : 0.0;

        // Calculate volatility (std dev of daily returns)
        $returns = [];
        for ($i = 0; $i < count($prices) - 1; $i++) {
            if ($prices[$i + 1] > 0) {
                $returns[] = ($prices[$i] - $prices[$i + 1]) / $prices[$i + 1];
            }
        }
        $volatilidade20d = ! empty($returns) ? $this->standardDeviation($returns) * 100 : 0.0;

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

    /**
     * Apply DB-stored API key and model to Laravel AI config at runtime.
     */
    private function applyRuntimeConfig(string $purpose): string
    {
        $config = $this->configResolver->resolve($purpose);
        $provider = $config['provider'];

        if (! empty($config['api_key'])) {
            config(["ai.providers.{$provider}.key" => $config['api_key']]);
        }

        if (! empty($config['settings']['model'])) {
            config(["ai.providers.{$provider}.model" => $config['settings']['model']]);
        }

        return $provider;
    }

    /**
     * Use LLM to generate rationale and percentage allocations.
     */
    private function generateRecommendation(
        array $candidateInfo,
        array $currentBasketSummary,
        int $limit,
    ): RecommendationResult {
        $provider = $this->applyRuntimeConfig('llm');

        $prompt = $this->buildRecommendationPrompt($candidateInfo, $currentBasketSummary, $limit);

        try {
            $agent = new AnonymousAgent(
                instructions: 'Você é um analista financeiro especializado em ações brasileiras. Responda APENAS em JSON válido, sem markdown.',
                messages: [],
                tools: [],
            );
            $response = $agent->prompt($prompt, provider: $provider);

            $parsed = json_decode($response->text, true);

            if (! is_array($parsed) || ! isset($parsed['suggestions'])) {
                return $this->fallbackRecommendation($candidateInfo, $currentBasketSummary);
            }

            $suggestedTickers = array_map(fn ($s) => [
                'ticker' => $s['ticker'],
                'percentual' => (float) ($s['percentual'] ?? 20.0),
                'similarityScore' => (float) ($s['similarity_score'] ?? 0.0),
                'rationale' => $s['rationale'] ?? '',
            ], array_slice($parsed['suggestions'], 0, $limit));

            return new RecommendationResult(
                suggestedTickers: $suggestedTickers,
                currentBasketSummary: $currentBasketSummary,
                confidence: (float) ($parsed['confidence'] ?? 0.5),
                generatedAt: new \DateTimeImmutable,
            );
        } catch (\Throwable) {
            return $this->fallbackRecommendation($candidateInfo, $currentBasketSummary);
        }
    }

    /**
     * Generate recommendation directly from LLM when no embeddings/cotações exist.
     */
    private function generateDirectLlmRecommendation(array $currentBasketSummary, int $limit): RecommendationResult
    {
        $provider = $this->applyRuntimeConfig('llm');
        $currentJson = json_encode($currentBasketSummary, JSON_PRETTY_PRINT);

        $prompt = <<<PROMPT
        Você é um analista financeiro especializado em ações brasileiras (B3).
        Analise a cesta Top Five atual e sugira uma nova composição otimizada de {$limit} ativos.

        Cesta atual:
        {$currentJson}

        Considere diversificação setorial, liquidez e fundamentos.
        Responda APENAS em JSON válido (sem markdown) com este formato:
        {
            "suggestions": [
                {"ticker": "XXXX3", "percentual": 25.0, "similarity_score": 0.85, "rationale": "Explicação breve em português"}
            ],
            "confidence": 0.65
        }

        Regras:
        - Use tickers reais da B3 (ex: PETR4, VALE3, ITUB4, WEGE3, ABEV3, BBDC4, etc.)
        - Os percentuais devem somar exatamente 100%
        - Cada percentual deve ser > 0
        - O confidence deve ser entre 0.5 e 0.7 (sem dados de mercado reais, a confiança é moderada)
        - A rationale deve mencionar o setor e motivo da sugestão
        PROMPT;

        try {
            $agent = new AnonymousAgent(
                instructions: 'Você é um analista financeiro especializado em ações brasileiras. Responda APENAS em JSON válido, sem markdown.',
                messages: [],
                tools: [],
            );
            $response = $agent->prompt($prompt, provider: $provider);

            $parsed = json_decode($response->text, true);

            if (! is_array($parsed) || ! isset($parsed['suggestions'])) {
                return $this->fallbackRecommendation([], $currentBasketSummary);
            }

            $suggestedTickers = array_map(fn ($s) => [
                'ticker' => $s['ticker'],
                'percentual' => (float) ($s['percentual'] ?? 20.0),
                'similarityScore' => (float) ($s['similarity_score'] ?? 0.0),
                'rationale' => $s['rationale'] ?? '',
            ], array_slice($parsed['suggestions'], 0, $limit));

            return new RecommendationResult(
                suggestedTickers: $suggestedTickers,
                currentBasketSummary: $currentBasketSummary,
                confidence: (float) ($parsed['confidence'] ?? 0.5),
                generatedAt: new \DateTimeImmutable,
            );
        } catch (\Throwable) {
            return $this->fallbackRecommendation([], $currentBasketSummary);
        }
    }

    private function buildRecommendationPrompt(array $candidateInfo, array $currentBasketSummary, int $limit): string
    {
        $currentJson = json_encode($currentBasketSummary, JSON_PRETTY_PRINT);
        $candidatesJson = json_encode($candidateInfo, JSON_PRETTY_PRINT);

        return <<<PROMPT
        Analise a cesta atual e os candidatos similares encontrados via embedding.
        Sugira uma nova composição de {$limit} ativos com percentuais que somem 100%.

        Cesta atual:
        {$currentJson}

        Candidatos (ordenados por similaridade):
        {$candidatesJson}

        Responda em JSON com este formato:
        {
            "suggestions": [
                {"ticker": "XXXX3", "percentual": 25.0, "similarity_score": 0.95, "rationale": "Explicação breve"}
            ],
            "confidence": 0.75
        }

        Regras:
        - Os percentuais devem somar exatamente 100%
        - Cada percentual deve ser > 0
        - O confidence deve refletir a qualidade dos dados disponíveis (0.0 a 1.0)
        - A rationale deve ser em português e mencionar dados concretos
        PROMPT;
    }

    /**
     * Fallback when LLM fails — distribute equally among candidates.
     */
    private function fallbackRecommendation(array $candidateInfo, array $currentBasketSummary): RecommendationResult
    {
        $count = count($candidateInfo);
        if ($count === 0) {
            return new RecommendationResult([], $currentBasketSummary, 0.0, new \DateTimeImmutable);
        }

        $percentual = round(100.0 / $count, 2);
        $suggestedTickers = array_map(fn ($c) => [
            'ticker' => $c['ticker'],
            'percentual' => $percentual,
            'similarityScore' => 1.0 - $c['distance'],
            'rationale' => 'Sugestão baseada em similaridade de perfil de mercado.',
        ], $candidateInfo);

        // Adjust last to sum exactly 100
        $sum = array_sum(array_column($suggestedTickers, 'percentual'));
        if (abs($sum - 100.0) > 0.01) {
            $suggestedTickers[count($suggestedTickers) - 1]['percentual'] += (100.0 - $sum);
        }

        return new RecommendationResult($suggestedTickers, $currentBasketSummary, 0.3, new \DateTimeImmutable);
    }

    /**
     * Parse pgvector string format "[1.0,2.0,...]" to float array.
     *
     * @return float[]|null
     */
    private function parseVectorString(string $vectorStr): ?array
    {
        $clean = trim($vectorStr, '[]');
        if (empty($clean)) {
            return null;
        }

        return array_map('floatval', explode(',', $clean));
    }

    private function standardDeviation(array $values): float
    {
        $count = count($values);
        if ($count < 2) {
            return 0.0;
        }

        $mean = array_sum($values) / $count;
        $variance = array_sum(array_map(fn ($v) => ($v - $mean) ** 2, $values)) / ($count - 1);

        return sqrt($variance);
    }
}
