<?php

namespace App\Domain\PurchaseEngine\Repositories;

interface CustodiaMasterRepositoryInterface
{
    /**
     * @param  string[]  $tickers
     * @return array<string, int> ticker => quantidade
     */
    public function getSaldosByTickers(array $tickers): array;

    public function updateSaldo(string $ticker, int $quantidade): void;
}
