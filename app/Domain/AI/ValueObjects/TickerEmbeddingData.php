<?php

declare(strict_types=1);

namespace App\Domain\AI\ValueObjects;

final readonly class TickerEmbeddingData
{
    public function __construct(
        public string $ticker,
        public float $precoFechamento,
        public float $precoAbertura,
        public float $precoMaximo,
        public float $precoMinimo,
        public float $volume,
        public float $variacao5d,
        public float $variacao20d,
        public float $volatilidade20d,
        public string $dataReferencia,
    ) {}

    /**
     * Produce a deterministic text representation suitable for embedding.
     */
    public function toEmbeddingText(): string
    {
        return sprintf(
            '%s | Fechamento: %.2f | Abertura: %.2f | Max: %.2f | Min: %.2f | Variacao 5d: %s%.2f%% | Variacao 20d: %s%.2f%% | Volume medio: %d | Volatilidade 20d: %.2f%%',
            $this->ticker,
            $this->precoFechamento,
            $this->precoAbertura,
            $this->precoMaximo,
            $this->precoMinimo,
            $this->variacao5d >= 0 ? '+' : '',
            $this->variacao5d,
            $this->variacao20d >= 0 ? '+' : '',
            $this->variacao20d,
            (int) $this->volume,
            $this->volatilidade20d,
        );
    }
}
