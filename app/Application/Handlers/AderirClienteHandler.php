<?php

namespace App\Application\Handlers;

use App\Application\Commands\AderirClienteCommand;
use App\Domain\Client\Entities\Cliente;
use App\Domain\Client\Entities\ContaGrafica;
use App\Domain\Client\Events\ClienteAderiu;
use App\Domain\Client\Repositories\ClienteRepositoryInterface;
use App\Domain\Client\Repositories\ContaGraficaRepositoryInterface;
use App\Domain\Client\ValueObjects\Cpf;
use App\Domain\Client\ValueObjects\Email;
use App\Domain\Client\ValueObjects\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AderirClienteHandler
{
    public function __construct(
        private ClienteRepositoryInterface $clienteRepository,
        private ContaGraficaRepositoryInterface $contaGraficaRepository,
    ) {}

    public function handle(AderirClienteCommand $command): array
    {
        $cpf = new Cpf($command->cpf);

        if ($this->clienteRepository->existsByCpf($cpf->value())) {
            throw new \DomainException('CLIENTE_CPF_DUPLICADO');
        }

        $cliente = new Cliente(
            id: (string) Str::uuid(),
            nome: $command->nome,
            cpf: $cpf,
            email: new Email($command->email),
            valorMensal: Money::fromDecimal($command->valorMensal),
        );

        $contaGrafica = new ContaGrafica(
            id: (string) Str::uuid(),
            clienteId: $cliente->id(),
            numero: ContaGrafica::gerarNumero(),
        );

        DB::transaction(function () use ($cliente, $contaGrafica) {
            $this->clienteRepository->save($cliente);
            $this->contaGraficaRepository->save($contaGrafica);
        });

        event(new ClienteAderiu(
            clienteId: $cliente->id(),
            nome: $cliente->nome(),
            cpf: $cliente->cpf()->value(),
            email: $cliente->email()->value(),
            valorMensal: $cliente->valorMensal()->toDecimalString(),
            contaGraficaNumero: $contaGrafica->numero(),
        ));

        return [
            'clienteId' => $cliente->id(),
            'contaGraficaNumero' => $contaGrafica->numero(),
        ];
    }
}
