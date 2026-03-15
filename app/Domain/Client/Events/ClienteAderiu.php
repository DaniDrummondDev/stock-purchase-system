<?php

namespace App\Domain\Client\Events;

class ClienteAderiu
{
    public function __construct(
        public readonly string $clienteId,
        public readonly string $nome,
        public readonly string $cpf,
        public readonly string $email,
        public readonly string $valorMensal,
        public readonly string $contaGraficaNumero,
    ) {}
}
