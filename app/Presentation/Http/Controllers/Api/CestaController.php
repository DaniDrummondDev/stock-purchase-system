<?php

namespace App\Presentation\Http\Controllers\Api;

use App\Application\Commands\AlterarCestaCommand;
use App\Application\Commands\CriarCestaCommand;
use App\Application\Handlers\AlterarCestaHandler;
use App\Application\Handlers\CriarCestaHandler;
use App\Application\Handlers\ObterCestaAtualHandler;
use App\Application\Handlers\ObterHistoricoCestaHandler;
use App\Application\Queries\ObterCestaAtualQuery;
use App\Application\Queries\ObterHistoricoCestaQuery;
use App\Domain\Basket\Repositories\CestaRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class CestaController extends Controller
{
    #[OA\Post(
        path: '/api/admin/cesta',
        summary: 'Criar ou atualizar cesta Top Five',
        tags: ['Cesta'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nome', 'ativos'],
                properties: [
                    new OA\Property(property: 'nome', type: 'string', example: 'Top Five - Março 2026'),
                    new OA\Property(
                        property: 'ativos',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'ticker', type: 'string', example: 'PETR4'),
                                new OA\Property(property: 'percentual', type: 'number', example: 30.00),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Cesta criada/atualizada com sucesso'),
            new OA\Response(response: 422, description: 'Dados inválidos'),
        ]
    )]
    public function store(
        Request $request,
        CestaRepositoryInterface $cestaRepository,
        CriarCestaHandler $criarHandler,
        AlterarCestaHandler $alterarHandler,
    ): JsonResponse {
        $validated = $this->validateCesta($request);

        try {
            $cestaAtiva = $cestaRepository->findAtiva();

            if ($cestaAtiva) {
                $result = $alterarHandler->handle(new AlterarCestaCommand(
                    nome: $validated['nome'],
                    ativos: $validated['ativos'],
                ));
                $message = 'Cesta atualizada com sucesso';
            } else {
                $result = $criarHandler->handle(new CriarCestaCommand(
                    nome: $validated['nome'],
                    ativos: $validated['ativos'],
                ));
                $message = 'Cesta criada com sucesso';
            }

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => $message,
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CESTA_ATIVOS_INVALIDOS',
                    'message' => $e->getMessage(),
                ],
            ], 422);
        }
    }

    #[OA\Get(
        path: '/api/admin/cesta/atual',
        summary: 'Obter cesta ativa atual',
        tags: ['Cesta'],
        responses: [
            new OA\Response(response: 200, description: 'Cesta ativa'),
            new OA\Response(response: 404, description: 'Nenhuma cesta ativa'),
        ]
    )]
    public function atual(ObterCestaAtualHandler $handler): JsonResponse
    {
        try {
            $result = $handler->handle(new ObterCestaAtualQuery);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => $e->getMessage(),
                    'message' => 'Nenhuma cesta ativa encontrada',
                ],
            ], 404);
        }
    }

    #[OA\Get(
        path: '/api/admin/cesta/historico',
        summary: 'Histórico de cestas',
        tags: ['Cesta'],
        responses: [
            new OA\Response(response: 200, description: 'Lista de todas as cestas'),
        ]
    )]
    public function historico(ObterHistoricoCestaHandler $handler): JsonResponse
    {
        $result = $handler->handle(new ObterHistoricoCestaQuery);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    private function validateCesta(Request $request): array
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'ativos' => 'required|array|size:5',
            'ativos.*.ticker' => 'required|string|max:12',
            'ativos.*.percentual' => 'required|numeric|gt:0|max:100',
        ]);

        $tickers = array_column($validated['ativos'], 'ticker');
        if (count($tickers) !== count(array_unique(array_map('strtoupper', $tickers)))) {
            abort(422, 'Tickers duplicados não são permitidos');
        }

        $sum = array_sum(array_column($validated['ativos'], 'percentual'));
        if (abs($sum - 100) > 0.01) {
            abort(422, sprintf('Soma dos percentuais deve ser 100%%, recebido %.2f%%', $sum));
        }

        return $validated;
    }
}
