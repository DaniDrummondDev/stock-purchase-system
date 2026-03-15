<?php

namespace App\Application\Handlers;

use App\Application\Queries\ObterCotacaoQuery;
use App\Domain\MarketData\Entities\Cotacao;
use App\Domain\MarketData\Repositories\CotacaoRepositoryInterface;

class ObterCotacaoHandler
{
    public function __construct(
        private CotacaoRepositoryInterface $cotacaoRepository,
    ) {}

    public function handle(ObterCotacaoQuery $query): array
    {
        if ($query->data) {
            $date = \DateTimeImmutable::createFromFormat('Y-m-d', $query->data);

            if ($date === false) {
                throw new \InvalidArgumentException('Data inválida. Use formato YYYY-MM-DD');
            }

            $cotacao = $this->cotacaoRepository->findByTickerAndDate($query->ticker, $date);
        } else {
            $cotacao = $this->cotacaoRepository->findLatestByTicker($query->ticker);
        }

        if (! $cotacao) {
            throw new \DomainException('COTACAO_NAO_ENCONTRADA');
        }

        return $this->formatCotacao($cotacao);
    }

    private function formatCotacao(Cotacao $cotacao): array
    {
        return [
            'ticker' => $cotacao->ticker(),
            'dataPregao' => $cotacao->dataPregao()->format('Y-m-d'),
            'precoFechamento' => number_format($cotacao->precoFechamento(), 2, '.', ''),
            'precoAbertura' => number_format($cotacao->precoAbertura(), 2, '.', ''),
            'precoMaximo' => number_format($cotacao->precoMaximo(), 2, '.', ''),
            'precoMinimo' => number_format($cotacao->precoMinimo(), 2, '.', ''),
            'tipoMercado' => $cotacao->tipoMercado(),
            'volume' => number_format($cotacao->volume(), 2, '.', ''),
        ];
    }
}
