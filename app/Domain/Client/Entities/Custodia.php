<?php

namespace App\Domain\Client\Entities;

use App\Domain\Client\ValueObjects\Money;
use InvalidArgumentException;

class Custodia
{
    private string $id;

    private string $clienteId;

    private string $ticker;

    private int $quantidade;

    private Money $precoMedio;

    public function __construct(
        string $id,
        string $clienteId,
        string $ticker,
        int $quantidade = 0,
        ?Money $precoMedio = null,
    ) {
        $this->id = $id;
        $this->clienteId = $clienteId;
        $this->ticker = strtoupper(trim($ticker));
        $this->quantidade = $quantidade;
        $this->precoMedio = $precoMedio ?? Money::zero();
    }

    public function id(): string
    {
        return $this->id;
    }

    public function clienteId(): string
    {
        return $this->clienteId;
    }

    public function ticker(): string
    {
        return $this->ticker;
    }

    public function quantidade(): int
    {
        return $this->quantidade;
    }

    public function precoMedio(): Money
    {
        return $this->precoMedio;
    }

    /**
     * RN-041/042: PM = (QtyOld × PMOld + QtyNew × PriceNew) / (QtyOld + QtyNew)
     */
    public function adicionarCompra(int $quantidade, Money $precoUnitario): void
    {
        if ($quantidade <= 0) {
            throw new InvalidArgumentException('Quantidade deve ser positiva');
        }

        $valorExistente = Money::fromCents($this->quantidade * $this->precoMedio->cents());
        $valorNovo = Money::fromCents($quantidade * $precoUnitario->cents());
        $totalQuantidade = $this->quantidade + $quantidade;

        $novoPrecoMedio = Money::fromCents(
            (int) floor(($valorExistente->cents() + $valorNovo->cents()) / $totalQuantidade)
        );

        $this->quantidade = $totalQuantidade;
        $this->precoMedio = $novoPrecoMedio;
    }

    /**
     * RN-043: Em vendas, o PM NÃO muda, apenas a quantidade diminui.
     */
    public function removerVenda(int $quantidade): void
    {
        if ($quantidade <= 0) {
            throw new InvalidArgumentException('Quantidade deve ser positiva');
        }

        if ($quantidade > $this->quantidade) {
            throw new InvalidArgumentException(
                "Quantidade insuficiente. Disponível: {$this->quantidade}, solicitado: {$quantidade}"
            );
        }

        $this->quantidade -= $quantidade;
    }

    public function valorAtual(Money $cotacaoAtual): Money
    {
        return Money::fromCents($this->quantidade * $cotacaoAtual->cents());
    }

    public function lucroOuPrejuizo(Money $cotacaoAtual): Money
    {
        $diferenca = $cotacaoAtual->cents() - $this->precoMedio->cents();

        return Money::fromCents($this->quantidade * $diferenca);
    }
}
