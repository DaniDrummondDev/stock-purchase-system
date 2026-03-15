<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\PurchaseEngine\Repositories\CustodiaMasterRepositoryInterface;
use App\Infrastructure\Persistence\Models\CustodiaMaster;

class EloquentCustodiaMasterRepository implements CustodiaMasterRepositoryInterface
{
    /**
     * @param  string[]  $tickers
     * @return array<string, int> ticker => quantidade
     */
    public function getSaldosByTickers(array $tickers): array
    {
        $tickers = array_map('strtoupper', $tickers);

        return CustodiaMaster::whereIn('ticker', $tickers)
            ->where('quantidade', '>', 0)
            ->pluck('quantidade', 'ticker')
            ->all();
    }

    public function updateSaldo(string $ticker, int $quantidade): void
    {
        $ticker = strtoupper($ticker);

        CustodiaMaster::updateOrCreate(
            ['ticker' => $ticker],
            ['quantidade' => $quantidade, 'preco_medio' => 0],
        );
    }
}
