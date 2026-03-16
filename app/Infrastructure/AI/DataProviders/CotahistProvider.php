<?php

declare(strict_types=1);

namespace App\Infrastructure\AI\DataProviders;

use App\Domain\AI\Contracts\DataProviderInterface;
use App\Domain\AI\Contracts\DataQuery;
use App\Domain\AI\Contracts\DataResult;
use App\Infrastructure\Persistence\Models\Cotacao;

final class CotahistProvider implements DataProviderInterface
{
    public function __construct(
        private readonly Cotacao $cotacao,
    ) {}

    public function getName(): string
    {
        return 'cotahist';
    }

    /** @return array<int, string> */
    public function getCapabilities(): array
    {
        return ['quotation', 'historical_prices', 'volume'];
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function query(DataQuery $query): DataResult
    {
        $data = match ($query->capability) {
            'quotation' => $this->queryQuotation($query->params),
            'historical_prices' => $this->queryHistoricalPrices($query->params),
            'volume' => $this->queryVolume($query->params),
            default => throw new \InvalidArgumentException(
                "Unsupported capability '{$query->capability}' for cotahist provider."
            ),
        };

        return new DataResult(
            data: $data,
            providerName: $this->getName(),
            fromCache: false,
            fetchedAt: new \DateTimeImmutable,
        );
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function queryQuotation(array $params): array
    {
        $this->requireParam($params, 'ticker');

        $builder = $this->cotacao->newQuery()
            ->where('ticker', $params['ticker']);

        if (isset($params['date'])) {
            $builder->where('data_pregao', $params['date']);
        } else {
            $builder->orderByDesc('data_pregao');
        }

        $cotacao = $builder->first();

        if ($cotacao === null) {
            return [];
        }

        return [
            'ticker' => $cotacao->ticker,
            'data_pregao' => $cotacao->data_pregao->format('Y-m-d'),
            'preco_abertura' => $cotacao->preco_abertura,
            'preco_maximo' => $cotacao->preco_maximo,
            'preco_minimo' => $cotacao->preco_minimo,
            'preco_fechamento' => $cotacao->preco_fechamento,
            'volume' => $cotacao->volume,
        ];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<int, array<string, mixed>>
     */
    private function queryHistoricalPrices(array $params): array
    {
        $this->requireParam($params, 'ticker');
        $this->requireParam($params, 'start_date');

        $builder = $this->cotacao->newQuery()
            ->where('ticker', $params['ticker'])
            ->where('data_pregao', '>=', $params['start_date']);

        if (isset($params['end_date'])) {
            $builder->where('data_pregao', '<=', $params['end_date']);
        }

        $results = $builder->orderBy('data_pregao')->get();

        return $results->map(fn (Cotacao $cotacao): array => [
            'ticker' => $cotacao->ticker,
            'data_pregao' => $cotacao->data_pregao->format('Y-m-d'),
            'preco_abertura' => $cotacao->preco_abertura,
            'preco_maximo' => $cotacao->preco_maximo,
            'preco_minimo' => $cotacao->preco_minimo,
            'preco_fechamento' => $cotacao->preco_fechamento,
            'volume' => $cotacao->volume,
        ])->all();
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function queryVolume(array $params): array
    {
        $this->requireParam($params, 'ticker');

        $builder = $this->cotacao->newQuery()
            ->where('ticker', $params['ticker']);

        if (isset($params['date'])) {
            $builder->where('data_pregao', $params['date']);
        } else {
            $builder->orderByDesc('data_pregao');
        }

        $cotacao = $builder->first();

        if ($cotacao === null) {
            return [];
        }

        return [
            'ticker' => $cotacao->ticker,
            'data_pregao' => $cotacao->data_pregao->format('Y-m-d'),
            'volume' => $cotacao->volume,
        ];
    }

    /**
     * @param  array<string, mixed>  $params
     *
     * @throws \InvalidArgumentException
     */
    private function requireParam(array $params, string $key): void
    {
        if (! isset($params[$key])) {
            throw new \InvalidArgumentException(
                "Missing required parameter '{$key}' for cotahist provider."
            );
        }
    }
}
