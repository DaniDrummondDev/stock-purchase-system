<?php

namespace App\Domain\Basket\Entities;

use InvalidArgumentException;

class Cesta
{
    private const REQUIRED_ATIVOS = 5;

    private const REQUIRED_SUM = 100.0;

    private string $id;

    private string $nome;

    private bool $ativo;

    /** @var CestaAtivo[] */
    private array $ativos;

    private ?\DateTimeImmutable $dataDesativacao;

    private \DateTimeImmutable $createdAt;

    /**
     * @param  CestaAtivo[]  $ativos
     */
    public function __construct(
        string $id,
        string $nome,
        array $ativos,
        bool $ativo = true,
        ?\DateTimeImmutable $dataDesativacao = null,
        ?\DateTimeImmutable $createdAt = null,
    ) {
        if (trim($nome) === '') {
            throw new InvalidArgumentException('Nome da cesta não pode ser vazio');
        }

        $this->validateAtivos($ativos);

        $this->id = $id;
        $this->nome = trim($nome);
        $this->ativos = array_values($ativos);
        $this->ativo = $ativo;
        $this->dataDesativacao = $dataDesativacao;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function nome(): string
    {
        return $this->nome;
    }

    public function isAtiva(): bool
    {
        return $this->ativo;
    }

    /**
     * @return CestaAtivo[]
     */
    public function ativos(): array
    {
        return $this->ativos;
    }

    public function dataDesativacao(): ?\DateTimeImmutable
    {
        return $this->dataDesativacao;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return string[]
     */
    public function tickers(): array
    {
        return array_map(
            fn (CestaAtivo $a) => $a->ticker()->value(),
            $this->ativos,
        );
    }

    public function percentualPorTicker(string $ticker): ?float
    {
        foreach ($this->ativos as $ativo) {
            if ($ativo->ticker()->value() === strtoupper($ticker)) {
                return $ativo->percentual()->value();
            }
        }

        return null;
    }

    public function desativar(): void
    {
        if (! $this->ativo) {
            throw new InvalidArgumentException('Cesta já está desativada');
        }

        $this->ativo = false;
        $this->dataDesativacao = new \DateTimeImmutable;
    }

    /**
     * @param  CestaAtivo[]  $ativos
     */
    private function validateAtivos(array $ativos): void
    {
        if (count($ativos) !== self::REQUIRED_ATIVOS) {
            throw new InvalidArgumentException(
                sprintf('Cesta deve conter exatamente %d ativos, %d fornecidos', self::REQUIRED_ATIVOS, count($ativos))
            );
        }

        $tickers = [];
        $sum = 0.0;

        foreach ($ativos as $ativo) {
            if (! $ativo instanceof CestaAtivo) {
                throw new InvalidArgumentException('Todos os itens devem ser instâncias de CestaAtivo');
            }

            $ticker = $ativo->ticker()->value();

            if (in_array($ticker, $tickers, true)) {
                throw new InvalidArgumentException(sprintf('Ticker duplicado: %s', $ticker));
            }

            $tickers[] = $ticker;
            $sum += $ativo->percentual()->value();
        }

        if (abs($sum - self::REQUIRED_SUM) > 0.01) {
            throw new InvalidArgumentException(
                sprintf('Soma dos percentuais deve ser 100%%, recebido %.2f%%', $sum)
            );
        }
    }
}
