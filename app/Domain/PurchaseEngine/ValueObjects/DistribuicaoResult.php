<?php

namespace App\Domain\PurchaseEngine\ValueObjects;

final class DistribuicaoResult
{
    /**
     * @param  array  $alocacoes  [['clienteId' => ..., 'ticker' => ..., 'quantidade' => ..., 'preco' => ...], ...]
     * @param  array<string, int>  $residuos  ticker => quantidade residual para master
     */
    public function __construct(
        public readonly array $alocacoes,
        public readonly array $residuos,
    ) {}
}
