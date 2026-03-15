<?php

namespace App\Presentation\Http\Controllers\Api;

use App\Application\Commands\AderirClienteCommand;
use App\Application\Commands\AlterarValorMensalCommand;
use App\Application\Commands\SairClienteCommand;
use App\Application\Handlers\AderirClienteHandler;
use App\Application\Handlers\AlterarValorMensalHandler;
use App\Application\Handlers\ObterCarteiraHandler;
use App\Application\Handlers\SairClienteHandler;
use App\Application\Queries\ObterCarteiraQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ClienteController extends Controller
{
    #[OA\Post(
        path: '/api/clientes/adesao',
        summary: 'Adesão de novo cliente',
        tags: ['Clientes'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nome', 'cpf', 'email', 'valorMensal'],
                properties: [
                    new OA\Property(property: 'nome', type: 'string', example: 'João Silva'),
                    new OA\Property(property: 'cpf', type: 'string', example: '12345678909'),
                    new OA\Property(property: 'email', type: 'string', example: 'joao@email.com'),
                    new OA\Property(property: 'valorMensal', type: 'number', example: 1000.00),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Cliente criado com sucesso'),
            new OA\Response(response: 409, description: 'CPF duplicado'),
            new OA\Response(response: 422, description: 'Dados inválidos'),
        ]
    )]
    public function adesao(Request $request, AderirClienteHandler $handler): JsonResponse
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'cpf' => 'required|string|size:11',
            'email' => 'required|email|max:255',
            'valorMensal' => 'required|numeric|min:100',
        ]);

        try {
            $result = $handler->handle(new AderirClienteCommand(
                nome: $validated['nome'],
                cpf: $validated['cpf'],
                email: $validated['email'],
                valorMensal: $validated['valorMensal'],
            ));

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Cliente aderiu com sucesso',
            ], 201);
        } catch (\DomainException $e) {
            $code = match ($e->getMessage()) {
                'CLIENTE_CPF_DUPLICADO' => 409,
                default => 422,
            };

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => $e->getMessage(),
                    'message' => $this->errorMessage($e->getMessage()),
                ],
            ], $code);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALOR_MENSAL_INVALIDO',
                    'message' => $e->getMessage(),
                ],
            ], 422);
        }
    }

    #[OA\Post(
        path: '/api/clientes/{clienteId}/saida',
        summary: 'Saída do cliente do programa',
        tags: ['Clientes'],
        parameters: [
            new OA\Parameter(name: 'clienteId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Cliente saiu com sucesso'),
            new OA\Response(response: 404, description: 'Cliente não encontrado'),
            new OA\Response(response: 422, description: 'Cliente já inativo'),
        ]
    )]
    public function saida(string $clienteId, SairClienteHandler $handler): JsonResponse
    {
        try {
            $handler->handle(new SairClienteCommand(clienteId: $clienteId));

            return response()->json([
                'success' => true,
                'message' => 'Cliente saiu do programa com sucesso',
            ]);
        } catch (\DomainException $e) {
            $code = match ($e->getMessage()) {
                'CLIENTE_NAO_ENCONTRADO' => 404,
                default => 422,
            };

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => $e->getMessage(),
                    'message' => $this->errorMessage($e->getMessage()),
                ],
            ], $code);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CLIENTE_JA_INATIVO',
                    'message' => $e->getMessage(),
                ],
            ], 422);
        }
    }

    #[OA\Put(
        path: '/api/clientes/{clienteId}/valor-mensal',
        summary: 'Alterar valor mensal do cliente',
        tags: ['Clientes'],
        parameters: [
            new OA\Parameter(name: 'clienteId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['valorMensal'],
                properties: [
                    new OA\Property(property: 'valorMensal', type: 'number', example: 1500.00),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Valor mensal alterado'),
            new OA\Response(response: 404, description: 'Cliente não encontrado'),
            new OA\Response(response: 422, description: 'Valor inválido'),
        ]
    )]
    public function alterarValorMensal(string $clienteId, Request $request, AlterarValorMensalHandler $handler): JsonResponse
    {
        $validated = $request->validate([
            'valorMensal' => 'required|numeric|min:100',
        ]);

        try {
            $handler->handle(new AlterarValorMensalCommand(
                clienteId: $clienteId,
                valorMensal: $validated['valorMensal'],
            ));

            return response()->json([
                'success' => true,
                'message' => 'Valor mensal alterado com sucesso',
            ]);
        } catch (\DomainException $e) {
            $code = match ($e->getMessage()) {
                'CLIENTE_NAO_ENCONTRADO' => 404,
                default => 422,
            };

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => $e->getMessage(),
                    'message' => $this->errorMessage($e->getMessage()),
                ],
            ], $code);
        }
    }

    #[OA\Get(
        path: '/api/clientes/{clienteId}/carteira',
        summary: 'Consultar carteira do cliente',
        tags: ['Clientes'],
        parameters: [
            new OA\Parameter(name: 'clienteId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Carteira do cliente'),
            new OA\Response(response: 404, description: 'Cliente não encontrado'),
        ]
    )]
    public function carteira(string $clienteId, ObterCarteiraHandler $handler): JsonResponse
    {
        try {
            $carteira = $handler->handle(new ObterCarteiraQuery(clienteId: $clienteId));

            return response()->json([
                'success' => true,
                'data' => $carteira,
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => $e->getMessage(),
                    'message' => $this->errorMessage($e->getMessage()),
                ],
            ], 404);
        }
    }

    private function errorMessage(string $code): string
    {
        return match ($code) {
            'CLIENTE_CPF_DUPLICADO' => 'Já existe um cliente cadastrado com este CPF',
            'CLIENTE_NAO_ENCONTRADO' => 'Cliente não encontrado',
            'CLIENTE_JA_INATIVO' => 'Cliente já está inativo',
            'VALOR_MENSAL_INVALIDO' => 'Valor mensal mínimo é R$ 100,00',
            default => $code,
        };
    }
}
