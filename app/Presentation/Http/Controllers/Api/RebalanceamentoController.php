<?php

namespace App\Presentation\Http\Controllers\Api;

use App\Application\Commands\ExecutarRebalanceamentoCommand;
use App\Application\Handlers\ExecutarRebalanceamentoHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class RebalanceamentoController extends Controller
{
    #[OA\Post(
        path: '/api/admin/rebalanceamento/executar',
        summary: 'Executar rebalanceamento tipo B (desvio de proporção)',
        tags: ['Rebalanceamento'],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'clienteId', type: 'string', format: 'uuid', description: 'Opcional — se vazio, rebalanceia todos'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Rebalanceamento executado'),
            new OA\Response(response: 422, description: 'Pré-condições não atendidas'),
        ]
    )]
    public function executar(Request $request, ExecutarRebalanceamentoHandler $handler): JsonResponse
    {
        $validated = $request->validate([
            'clienteId' => 'nullable|string',
        ]);

        try {
            $result = $handler->handle(new ExecutarRebalanceamentoCommand(
                tipo: 'B',
                clienteId: $validated['clienteId'] ?? null,
            ));

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => sprintf(
                    'Rebalanceamento executado: %d de %d clientes rebalanceados',
                    $result['totalClientesRebalanceados'],
                    $result['totalClientesAnalisados'],
                ),
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => $e->getMessage(),
                    'message' => $this->errorMessage($e->getMessage()),
                ],
            ], 422);
        }
    }

    private function errorMessage(string $code): string
    {
        return match ($code) {
            'CESTA_NAO_ENCONTRADA' => 'Nenhuma cesta ativa encontrada',
            'NENHUM_CLIENTE_ATIVO' => 'Nenhum cliente ativo encontrado',
            default => $code,
        };
    }
}
