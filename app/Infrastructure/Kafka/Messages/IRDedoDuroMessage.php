<?php

namespace App\Infrastructure\Kafka\Messages;

use App\Domain\Tax\Events\IRDedoDuroCalculado;

class IRDedoDuroMessage
{
    public static function fromEvent(IRDedoDuroCalculado $event): array
    {
        return [
            'tipo' => 'IR_DEDO_DURO',
            'clienteId' => $event->clienteId,
            'cpf' => $event->cpf,
            'ticker' => $event->ticker,
            'tipoOperacao' => 'COMPRA',
            'quantidade' => $event->quantidade,
            'precoUnitario' => $event->precoUnitario,
            'valorOperacao' => $event->valorOperacao,
            'aliquota' => 0.00005,
            'valorIR' => $event->valorIR,
            'dataOperacao' => $event->dataOperacao,
            'dataCalculo' => now()->toIso8601String(),
        ];
    }
}
