<?php

declare(strict_types=1);

namespace App\Infrastructure\Kafka\Messages;

class RiscoAlertaMessage
{
    public function __construct(
        public readonly string $clienteId,
        public readonly float $score,
        public readonly string $band,
        public readonly array $alertas,
    ) {}

    public function toArray(): array
    {
        return [
            'tipo' => 'alerta_risco',
            'clienteId' => $this->clienteId,
            'score' => $this->score,
            'band' => $this->band,
            'alertas' => $this->alertas,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    public static function topic(): string
    {
        return config('kafka.topics.alertas_risco', 'alertas-risco');
    }
}
