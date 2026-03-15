<?php

namespace App\Infrastructure\Kafka\Messages;

use App\Domain\Tax\Events\IRVendaCalculado;

class IRVendaMessage
{
    public static function fromEvent(IRVendaCalculado $event): array
    {
        return [
            'tipo' => 'IR_VENDA',
            'clienteId' => $event->clienteId,
            'cpf' => $event->cpf,
            'mesReferencia' => $event->mesReferencia,
            'totalVendasMes' => $event->totalVendas,
            'isento' => $event->isento,
            'lucroLiquido' => $event->lucroLiquido,
            'aliquota' => $event->isento ? 0 : 0.20,
            'valorIR' => $event->valorIR,
            'detalhes' => $event->detalhes,
            'dataCalculo' => now()->toIso8601String(),
        ];
    }
}
