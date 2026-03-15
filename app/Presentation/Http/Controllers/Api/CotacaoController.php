<?php

namespace App\Presentation\Http\Controllers\Api;

use App\Application\Commands\ImportarCotahistCommand;
use App\Application\Handlers\ImportarCotahistHandler;
use App\Application\Handlers\ObterCotacaoHandler;
use App\Application\Queries\ObterCotacaoQuery;
use App\Jobs\ImportarCotahistJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class CotacaoController extends Controller
{
    #[OA\Post(
        path: '/api/admin/cotacoes/importar',
        summary: 'Importar arquivo COTAHIST da B3',
        tags: ['Cotações'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['filePath'],
                properties: [
                    new OA\Property(property: 'filePath', type: 'string', example: 'cotacoes/COTAHIST_D20260225.TXT'),
                    new OA\Property(property: 'async', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Importação concluída (sync)'),
            new OA\Response(response: 202, description: 'Importação enfileirada (async)'),
            new OA\Response(response: 422, description: 'Arquivo inválido'),
        ]
    )]
    public function importar(Request $request, ImportarCotahistHandler $handler): JsonResponse
    {
        $validated = $request->validate([
            'filePath' => 'required|string',
            'async' => 'boolean',
        ]);

        $filePath = base_path($validated['filePath']);

        if (! file_exists($filePath)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'ARQUIVO_NAO_ENCONTRADO',
                    'message' => 'Arquivo COTAHIST não encontrado',
                ],
            ], 422);
        }

        $async = $validated['async'] ?? true;

        if ($async) {
            ImportarCotahistJob::dispatch($filePath);

            return response()->json([
                'success' => true,
                'message' => 'Importação enfileirada para processamento',
            ], 202);
        }

        try {
            $stats = $handler->handle(new ImportarCotahistCommand(filePath: $filePath));

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Importação concluída',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'COTAHIST_INVALIDO',
                    'message' => $e->getMessage(),
                ],
            ], 422);
        }
    }

    #[OA\Get(
        path: '/api/cotacoes/{ticker}',
        summary: 'Cotação mais recente de um ativo',
        tags: ['Cotações'],
        parameters: [
            new OA\Parameter(name: 'ticker', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Cotação encontrada'),
            new OA\Response(response: 404, description: 'Cotação não encontrada'),
        ]
    )]
    public function show(string $ticker, ObterCotacaoHandler $handler): JsonResponse
    {
        try {
            $result = $handler->handle(new ObterCotacaoQuery(ticker: $ticker));

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => $e->getMessage(),
                    'message' => 'Cotação não encontrada para este ticker',
                ],
            ], 404);
        }
    }

    #[OA\Get(
        path: '/api/cotacoes/{ticker}/{data}',
        summary: 'Cotação de um ativo em data específica',
        tags: ['Cotações'],
        parameters: [
            new OA\Parameter(name: 'ticker', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'data', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Cotação encontrada'),
            new OA\Response(response: 404, description: 'Cotação não encontrada'),
        ]
    )]
    public function showByDate(string $ticker, string $data, ObterCotacaoHandler $handler): JsonResponse
    {
        try {
            $result = $handler->handle(new ObterCotacaoQuery(ticker: $ticker, data: $data));

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => $e->getMessage(),
                    'message' => 'Cotação não encontrada',
                ],
            ], 404);
        }
    }
}
