<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\MarketData\Entities\Cotacao as CotacaoEntity;
use App\Domain\MarketData\Repositories\CotacaoRepositoryInterface;
use App\Infrastructure\Persistence\Models\Cotacao as CotacaoModel;

class EloquentCotacaoRepository implements CotacaoRepositoryInterface
{
    public function save(CotacaoEntity $cotacao): void
    {
        $this->saveMany([$cotacao]);
    }

    /**
     * @param  CotacaoEntity[]  $cotacoes
     */
    public function saveMany(array $cotacoes): void
    {
        if (empty($cotacoes)) {
            return;
        }

        $rows = array_map(fn (CotacaoEntity $c) => [
            'ticker' => $c->ticker(),
            'data_pregao' => $c->dataPregao()->format('Y-m-d'),
            'preco_fechamento' => $c->precoFechamento(),
            'preco_abertura' => $c->precoAbertura(),
            'preco_maximo' => $c->precoMaximo(),
            'preco_minimo' => $c->precoMinimo(),
            'tipo_mercado' => $c->tipoMercado(),
            'cod_bdi' => $c->codBdi(),
            'volume' => $c->volume(),
            'created_at' => now(),
            'updated_at' => now(),
        ], $cotacoes);

        CotacaoModel::upsert(
            $rows,
            ['ticker', 'data_pregao', 'tipo_mercado'],
            ['preco_fechamento', 'preco_abertura', 'preco_maximo', 'preco_minimo', 'volume', 'cod_bdi', 'updated_at'],
        );
    }

    public function findByTickerAndDate(string $ticker, \DateTimeImmutable $date): ?CotacaoEntity
    {
        $model = CotacaoModel::where('ticker', strtoupper($ticker))
            ->where('data_pregao', $date->format('Y-m-d'))
            ->where('tipo_mercado', 'padrao')
            ->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function findLatestByTicker(string $ticker): ?CotacaoEntity
    {
        $model = CotacaoModel::where('ticker', strtoupper($ticker))
            ->where('tipo_mercado', 'padrao')
            ->orderBy('data_pregao', 'desc')
            ->first();

        return $model ? $this->toEntity($model) : null;
    }

    /**
     * @param  string[]  $tickers
     * @return CotacaoEntity[]
     */
    public function findLatestByTickers(array $tickers): array
    {
        $tickers = array_map('strtoupper', $tickers);

        $models = CotacaoModel::whereIn('ticker', $tickers)
            ->where('tipo_mercado', 'padrao')
            ->whereIn('data_pregao', function ($query) use ($tickers) {
                $query->selectRaw('MAX(data_pregao)')
                    ->from('cotacoes')
                    ->whereIn('ticker', $tickers)
                    ->where('tipo_mercado', 'padrao')
                    ->groupBy('ticker');
            })
            ->get();

        return $models->map(fn (CotacaoModel $m) => $this->toEntity($m))->all();
    }

    private function toEntity(CotacaoModel $model): CotacaoEntity
    {
        return new CotacaoEntity(
            ticker: $model->ticker,
            dataPregao: \DateTimeImmutable::createFromMutable($model->data_pregao->toDateTime()),
            precoFechamento: (float) $model->preco_fechamento,
            precoAbertura: (float) $model->preco_abertura,
            precoMaximo: (float) $model->preco_maximo,
            precoMinimo: (float) $model->preco_minimo,
            tipoMercado: $model->tipo_mercado,
            codBdi: $model->cod_bdi,
            volume: (float) $model->volume,
        );
    }
}
