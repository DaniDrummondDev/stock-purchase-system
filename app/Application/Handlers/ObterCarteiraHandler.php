<?php

namespace App\Application\Handlers;

use App\Application\Queries\ObterCarteiraQuery;
use App\Domain\Client\Repositories\ClienteRepositoryInterface;
use App\Domain\Client\Repositories\CustodiaRepositoryInterface;

class ObterCarteiraHandler
{
    public function __construct(
        private ClienteRepositoryInterface $clienteRepository,
        private CustodiaRepositoryInterface $custodiaRepository,
    ) {}

    public function handle(ObterCarteiraQuery $query): array
    {
        $cliente = $this->clienteRepository->findById($query->clienteId);

        if (! $cliente) {
            throw new \DomainException('CLIENTE_NAO_ENCONTRADO');
        }

        $custodias = $this->custodiaRepository->findByClienteId($query->clienteId);

        $ativos = array_map(fn ($custodia) => [
            'ticker' => $custodia->ticker(),
            'quantidade' => $custodia->quantidade(),
            'precoMedio' => $custodia->precoMedio()->toDecimalString(),
        ], $custodias);

        return [
            'clienteId' => $cliente->id(),
            'nome' => $cliente->nome(),
            'cpf' => $cliente->cpf()->formatted(),
            'status' => $cliente->status(),
            'valorMensal' => $cliente->valorMensal()->toDecimalString(),
            'valorTotalInvestido' => $cliente->valorTotalInvestido()->toDecimalString(),
            'ativos' => $ativos,
        ];
    }
}
