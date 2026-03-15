<?php

namespace App\Application\Handlers;

use App\Application\Commands\AlterarValorMensalCommand;
use App\Domain\Client\Events\ValorMensalAlterado;
use App\Domain\Client\Repositories\ClienteRepositoryInterface;
use App\Domain\Client\ValueObjects\Money;

class AlterarValorMensalHandler
{
    public function __construct(
        private ClienteRepositoryInterface $clienteRepository,
    ) {}

    public function handle(AlterarValorMensalCommand $command): void
    {
        $cliente = $this->clienteRepository->findById($command->clienteId);

        if (! $cliente) {
            throw new \DomainException('CLIENTE_NAO_ENCONTRADO');
        }

        $valorAnterior = $cliente->valorMensal()->toDecimalString();

        $cliente->alterarValorMensal(Money::fromDecimal($command->valorMensal));

        $this->clienteRepository->save($cliente);

        event(new ValorMensalAlterado(
            clienteId: $cliente->id(),
            valorAnterior: $valorAnterior,
            valorNovo: $cliente->valorMensal()->toDecimalString(),
        ));
    }
}
