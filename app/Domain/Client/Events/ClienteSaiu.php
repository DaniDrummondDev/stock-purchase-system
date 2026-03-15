<?php

namespace App\Domain\Client\Events;

class ClienteSaiu
{
    public function __construct(
        public readonly string $clienteId,
    ) {}
}
