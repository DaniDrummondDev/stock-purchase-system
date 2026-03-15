<?php

namespace App\Application\Handlers;

use App\Application\Commands\SairClienteCommand;
use App\Domain\Client\Events\ClienteSaiu;
use App\Domain\Client\Repositories\ClienteRepositoryInterface;

class SairClienteHandler
{
    public function __construct(
        private ClienteRepositoryInterface $clienteRepository,
    ) {}

    public function handle(SairClienteCommand $command): void
    {
        $cliente = $this->clienteRepository->findById($command->clienteId);

        if (! $cliente) {
            throw new \DomainException('CLIENTE_NAO_ENCONTRADO');
        }

        $cliente->sair();

        $this->clienteRepository->save($cliente);

        event(new ClienteSaiu(clienteId: $cliente->id()));
    }
}
