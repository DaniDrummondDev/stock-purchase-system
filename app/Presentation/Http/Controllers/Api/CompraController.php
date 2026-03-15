<?php

namespace App\Presentation\Http\Controllers\Api;

use App\Application\Commands\ExecutarCompraCommand;
use App\Application\Handlers\ExecutarCompraHandler;
use App\Domain\PurchaseEngine\Repositories\CompraProgramadaRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class CompraController extends Controller
{
    #[OA\Post(
        path: '/api/admin/motor/executar-compra',
        summary: 'Executar compra programada',
        tags: ['Motor de Compra'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['dataExecucao'],
                properties: [
                    new OA\Property(property: 'dataExecucao', type: 'string', format: 'date', example: '2026-02-25'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Compra executada com sucesso'),
            new OA\Response(response: 409, description: 'Compra já executada para esta data'),
            new OA\Response(response: 422, description: 'Dados inválidos ou pré-condições não atendidas'),
        ]
    )]
    public function executar(Request $request, ExecutarCompraHandler $handler): JsonResponse
    {
        $validated = $request->validate([
            'dataExecucao' => 'required|date_format:Y-m-d',
        ]);

        try {
            $result = $handler->handle(new ExecutarCompraCommand(
                dataExecucao: $validated['dataExecucao'],
            ));

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Compra executada com sucesso',
            ], 201);
        } catch (\DomainException $e) {
            $code = match ($e->getMessage()) {
                'COMPRA_JA_EXECUTADA' => 409,
                'CESTA_NAO_ENCONTRADA', 'NENHUM_CLIENTE_ATIVO', 'COTACOES_NAO_ENCONTRADAS' => 422,
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
                    'code' => 'DADOS_INVALIDOS',
                    'message' => $e->getMessage(),
                ],
            ], 422);
        }
    }

    #[OA\Get(
        path: '/api/admin/motor/compras',
        summary: 'Listar compras programadas',
        tags: ['Motor de Compra'],
        responses: [
            new OA\Response(response: 200, description: 'Lista de compras'),
        ]
    )]
    public function index(CompraProgramadaRepositoryInterface $repository): JsonResponse
    {
        $compras = $repository->findAll();

        return response()->json([
            'success' => true,
            'data' => array_map(fn ($c) => [
                'id' => $c->id,
                'dataExecucao' => $c->data_execucao->format('Y-m-d'),
                'status' => $c->status,
                'valorTotal' => $c->valor_total,
                'totalParticipantes' => $c->participantes->count(),
                'totalDistribuicoes' => $c->distribuicoes->count(),
            ], $compras),
        ]);
    }

    #[OA\Get(
        path: '/api/admin/motor/compras/{id}',
        summary: 'Detalhes de uma compra programada',
        tags: ['Motor de Compra'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detalhes da compra'),
            new OA\Response(response: 404, description: 'Compra não encontrada'),
        ]
    )]
    public function show(string $id, CompraProgramadaRepositoryInterface $repository): JsonResponse
    {
        $compra = $repository->findById($id);

        if (! $compra) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'COMPRA_NAO_ENCONTRADA',
                    'message' => 'Compra não encontrada',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $compra->id,
                'dataExecucao' => $compra->data_execucao->format('Y-m-d'),
                'status' => $compra->status,
                'valorTotal' => $compra->valor_total,
                'participantes' => $compra->participantes->map(fn ($p) => [
                    'clienteId' => $p->cliente_id,
                    'valorAporte' => $p->valor_aporte,
                ])->all(),
                'distribuicoes' => $compra->distribuicoes->map(fn ($d) => [
                    'clienteId' => $d->cliente_id,
                    'ticker' => $d->ticker,
                    'quantidade' => $d->quantidade,
                    'valor' => $d->valor,
                    'precoUnitario' => $d->preco_unitario,
                    'tipoLote' => $d->tipo_lote,
                ])->all(),
            ],
        ]);
    }

    private function errorMessage(string $code): string
    {
        return match ($code) {
            'COMPRA_JA_EXECUTADA' => 'Já existe uma compra executada para esta data',
            'CESTA_NAO_ENCONTRADA' => 'Nenhuma cesta ativa encontrada',
            'NENHUM_CLIENTE_ATIVO' => 'Nenhum cliente ativo para participar',
            'COTACOES_NAO_ENCONTRADAS' => 'Cotações não encontradas para os ativos da cesta',
            default => $code,
        };
    }
}
