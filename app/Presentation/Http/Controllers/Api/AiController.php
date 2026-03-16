<?php

namespace App\Presentation\Http\Controllers\Api;

use App\Domain\AI\Contracts\RecommendationServiceInterface;
use App\Domain\AI\Contracts\TriggerType;
use App\Domain\Basket\Repositories\CestaRepositoryInterface;
use App\Infrastructure\AI\Agents\PortfolioAnalystAgent;
use App\Infrastructure\AI\Orchestrator\AgentOrchestrator;
use App\Infrastructure\Persistence\Models\ChatContexto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AiController extends Controller
{
    #[OA\Post(
        path: '/api/ai/recomendacao-cesta',
        summary: 'Gerar recomendação IA para cesta de ações',
        tags: ['AI'],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'limit', type: 'integer', example: 5, description: 'Número de sugestões (1-10)'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Recomendação gerada com sucesso'),
            new OA\Response(response: 404, description: 'Cesta ativa não encontrada'),
            new OA\Response(response: 503, description: 'Serviço de IA indisponível'),
        ]
    )]
    public function recomendacaoCesta(
        Request $request,
        RecommendationServiceInterface $recommendationService,
        CestaRepositoryInterface $cestaRepo,
    ): JsonResponse {
        $validated = $request->validate([
            'limit' => 'sometimes|integer|min:1|max:10',
        ]);

        $limit = $validated['limit'] ?? 5;

        try {
            $cesta = $cestaRepo->findAtiva();

            if ($cesta === null) {
                return response()->json([
                    'error' => 'CESTA_NAO_ENCONTRADA',
                    'message' => 'Não existe cesta ativa no sistema.',
                ], 404);
            }

            $result = $recommendationService->recommendForCesta($cesta->id(), $limit);

            return response()->json([
                'data' => [
                    'suggestions' => $result->suggestedTickers,
                    'currentBasket' => $result->currentBasketSummary,
                    'confidence' => $result->confidence,
                    'generatedAt' => $result->generatedAt->format('c'),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'AI_SERVICE_ERROR',
                'message' => 'Erro ao gerar recomendação: '.$e->getMessage(),
            ], 503);
        }
    }

    #[OA\Post(
        path: '/api/ai/chat',
        summary: 'Chat com assistente financeiro IA',
        tags: ['AI'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['cliente_id', 'message'],
                properties: [
                    new OA\Property(property: 'cliente_id', type: 'string', format: 'uuid', description: 'UUID do cliente'),
                    new OA\Property(property: 'message', type: 'string', example: 'Como está minha carteira?'),
                    new OA\Property(property: 'session_id', type: 'string', format: 'uuid', description: 'UUID da sessão (opcional)'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Resposta do assistente'),
            new OA\Response(response: 422, description: 'Dados inválidos'),
            new OA\Response(response: 503, description: 'Serviço de IA indisponível'),
        ]
    )]
    public function chat(
        Request $request,
        AgentOrchestrator $orchestrator,
        PortfolioAnalystAgent $portfolioAgent,
    ): JsonResponse {
        $validated = $request->validate([
            'cliente_id' => 'required|uuid|exists:clientes,id',
            'message' => 'required|string|max:2000',
            'session_id' => 'sometimes|uuid',
        ]);

        try {
            // Load or create chat session
            $chatContexto = $this->getOrCreateChatContexto(
                $validated['cliente_id'],
                $validated['session_id'] ?? null,
            );

            // Append user message
            $chatContexto->appendMessage('user', $validated['message']);
            $chatContexto->save();

            // Configure orchestrator with available agents
            $orchestrator = $orchestrator
                ->forCliente($validated['cliente_id'], TriggerType::Chat)
                ->withAgents([$portfolioAgent]);

            // Send through laravel/ai
            $response = $orchestrator->prompt($validated['message']);

            $assistantMessage = $response->text();

            // Append assistant response
            $chatContexto->appendMessage('assistant', $assistantMessage);
            $chatContexto->save();

            return response()->json([
                'data' => [
                    'session_id' => $chatContexto->id,
                    'response' => $assistantMessage,
                    'agent_executions' => [],
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'AI_SERVICE_ERROR',
                'message' => 'Erro no chat: '.$e->getMessage(),
            ], 503);
        }
    }

    private function getOrCreateChatContexto(string $clienteId, ?string $sessionId): ChatContexto
    {
        if ($sessionId) {
            $existing = ChatContexto::find($sessionId);
            if ($existing && $existing->cliente_id === $clienteId) {
                return $existing;
            }
        }

        return ChatContexto::create([
            'cliente_id' => $clienteId,
            'mensagens' => [],
        ]);
    }
}
