<?php

namespace App\Domain\Client\Entities;

use App\Domain\Client\ValueObjects\Cpf;
use App\Domain\Client\ValueObjects\Email;
use App\Domain\Client\ValueObjects\Money;
use InvalidArgumentException;

class Cliente
{
    public const VALOR_MENSAL_MINIMO = 10000; // R$ 100,00 em centavos

    public const STATUS_ATIVO = 'ativo';

    public const STATUS_INATIVO = 'inativo';

    private string $id;

    private string $nome;

    private Cpf $cpf;

    private Email $email;

    private Money $valorMensal;

    private string $status;

    private Money $valorTotalInvestido;

    public function __construct(
        string $id,
        string $nome,
        Cpf $cpf,
        Email $email,
        Money $valorMensal,
    ) {
        $this->id = $id;
        $this->setNome($nome);
        $this->cpf = $cpf;
        $this->email = $email;
        $this->setValorMensal($valorMensal);
        $this->status = self::STATUS_ATIVO;
        $this->valorTotalInvestido = Money::zero();
    }

    public function id(): string
    {
        return $this->id;
    }

    public function nome(): string
    {
        return $this->nome;
    }

    public function cpf(): Cpf
    {
        return $this->cpf;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function valorMensal(): Money
    {
        return $this->valorMensal;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function valorTotalInvestido(): Money
    {
        return $this->valorTotalInvestido;
    }

    public function isAtivo(): bool
    {
        return $this->status === self::STATUS_ATIVO;
    }

    /**
     * RN-007: Ao sair, status muda para inativo.
     * RN-008: Posição na custódia é mantida.
     * RN-009: Não participa mais das compras programadas.
     */
    public function sair(): void
    {
        if (! $this->isAtivo()) {
            throw new InvalidArgumentException('Cliente já está inativo');
        }

        $this->status = self::STATUS_INATIVO;
    }

    /**
     * RN-011: O cliente pode alterar o valor mensal a qualquer momento.
     * RN-003: Valor mínimo R$ 100,00.
     */
    public function alterarValorMensal(Money $novoValor): void
    {
        $this->setValorMensal($novoValor);
    }

    /**
     * Calcula o valor do aporte por data de compra (1/3 do valor mensal).
     */
    public function valorAportePorCompra(): Money
    {
        return $this->valorMensal->divideBy(3);
    }

    public function adicionarInvestimento(Money $valor): void
    {
        $this->valorTotalInvestido = $this->valorTotalInvestido->add($valor);
    }

    private function setNome(string $nome): void
    {
        $nome = trim($nome);

        if (empty($nome)) {
            throw new InvalidArgumentException('Nome não pode ser vazio');
        }

        $this->nome = $nome;
    }

    private function setValorMensal(Money $valor): void
    {
        if ($valor->cents() < self::VALOR_MENSAL_MINIMO) {
            throw new InvalidArgumentException(
                'Valor mensal mínimo é R$ 100,00'
            );
        }

        $this->valorMensal = $valor;
    }
}
