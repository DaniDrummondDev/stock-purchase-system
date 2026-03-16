<?php

declare(strict_types=1);

namespace App\Domain\AI\ValueObjects;

final readonly class PortfolioAnalysis
{
    /**
     * @param  array<int, array{ticker: string, targetPercentual: float, actualPercentual: float, deviationPp: float}>  $composition
     * @param  array<int, array{ticker: string, quantidade: int, precoMedio: float, cotacaoAtual: float, custoTotal: float, valorAtual: float, lucroOuPrejuizo: float, percentual: float}>  $estimatedPL
     */
    public function __construct(
        public array $composition,
        public array $estimatedPL,
        public float $totalCusto,
        public float $totalValorAtual,
        public float $totalPL,
        public float $totalPLPercentual,
    ) {}
}
