<?php

namespace App\Domain\MarketData\Repositories;

use App\Domain\MarketData\Entities\Cotacao;

interface CotacaoRepositoryInterface
{
    public function save(Cotacao $cotacao): void;

    /**
     * @param  Cotacao[]  $cotacoes
     */
    public function saveMany(array $cotacoes): void;

    public function findByTickerAndDate(string $ticker, \DateTimeImmutable $date): ?Cotacao;

    public function findLatestByTicker(string $ticker): ?Cotacao;

    /**
     * @param  string[]  $tickers
     * @return Cotacao[]
     */
    public function findLatestByTickers(array $tickers): array;
}
