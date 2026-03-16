<?php

namespace App\Presentation\Livewire\Chat;

use App\Domain\AI\Contracts\TriggerType;
use App\Infrastructure\AI\Agents\EducatorAgent;
use App\Infrastructure\AI\Agents\MarketIntelligenceAgent;
use App\Infrastructure\AI\Agents\PortfolioAnalystAgent;
use App\Infrastructure\AI\Agents\RiskAnalystAgent;
use App\Infrastructure\AI\Agents\SimulatorAgent;
use App\Infrastructure\AI\Agents\TaxAnalystAgent;
use App\Infrastructure\AI\Orchestrator\AgentOrchestrator;
use App\Infrastructure\AI\Safety\ScopeGuardrail;
use App\Infrastructure\Persistence\Models\ChatContexto;
use App\Infrastructure\Persistence\Models\ChatMessage;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ChatWindow extends Component
{
    public array $messages = [];

    public string $currentMessage = '';

    public ?string $sessionId = null;

    public bool $loading = false;

    public string $error = '';

    public function mount(): void
    {
        // Load or create session for current user
        $user = Auth::user();
        if (! $user || ! $user->cliente_id) {
            return;
        }

        $session = ChatContexto::where('cliente_id', $user->cliente_id)
            ->orderBy('updated_at', 'desc')
            ->first();

        if ($session) {
            $this->sessionId = $session->id;
            $this->loadHistory();
        }
    }

    public function sendMessage(): void
    {
        if (empty(trim($this->currentMessage))) {
            return;
        }

        $user = Auth::user();
        if (! $user || ! $user->cliente_id) {
            $this->error = 'Utilizador não associado a um cliente.';

            return;
        }

        $this->loading = true;
        $this->error = '';

        // Add user message to UI immediately
        $this->messages[] = [
            'role' => 'user',
            'content' => $this->currentMessage,
            'created_at' => now()->format('H:i'),
        ];

        $messageText = $this->currentMessage;
        $this->currentMessage = '';

        try {
            // Check guardrails
            $guardrail = app(ScopeGuardrail::class);
            $blocked = $guardrail->hasBlockedContent($messageText);
            if ($blocked) {
                $this->messages[] = [
                    'role' => 'assistant',
                    'content' => $blocked,
                    'created_at' => now()->format('H:i'),
                ];
                $this->loading = false;

                return;
            }

            // Get or create session
            $session = $this->getOrCreateSession($user->cliente_id);
            $this->sessionId = $session->id;

            // Save user message
            ChatMessage::create([
                'session_id' => $session->id,
                'cliente_id' => $user->cliente_id,
                'role' => 'user',
                'content' => $messageText,
                'created_at' => now(),
            ]);

            // Get orchestrator with all agents
            $orchestrator = app(AgentOrchestrator::class)
                ->forCliente($user->cliente_id, TriggerType::Chat)
                ->withAgents([
                    app(PortfolioAnalystAgent::class),
                    app(RiskAnalystAgent::class),
                    app(TaxAnalystAgent::class),
                    app(MarketIntelligenceAgent::class),
                    app(SimulatorAgent::class),
                    app(EducatorAgent::class),
                ]);

            $response = $orchestrator->prompt($messageText);
            $assistantMessage = $response->text();

            // Save assistant message
            ChatMessage::create([
                'session_id' => $session->id,
                'cliente_id' => $user->cliente_id,
                'role' => 'assistant',
                'content' => $assistantMessage,
                'created_at' => now(),
            ]);

            // Add to UI
            $this->messages[] = [
                'role' => 'assistant',
                'content' => $assistantMessage,
                'created_at' => now()->format('H:i'),
            ];

            $session->touch();

        } catch (\Throwable $e) {
            $this->error = 'Erro ao processar mensagem. Tente novamente.';
            $this->messages[] = [
                'role' => 'assistant',
                'content' => 'Desculpe, ocorreu um erro ao processar sua mensagem. Tente novamente.',
                'created_at' => now()->format('H:i'),
            ];
        }

        $this->loading = false;
    }

    public function newSession(): void
    {
        $this->sessionId = null;
        $this->messages = [];
        $this->currentMessage = '';
        $this->error = '';
    }

    private function loadHistory(): void
    {
        if (! $this->sessionId) {
            return;
        }

        $records = ChatMessage::where('session_id', $this->sessionId)
            ->orderBy('created_at')
            ->limit(50)
            ->get();

        $this->messages = $records->map(fn ($m) => [
            'role' => $m->role,
            'content' => $m->content,
            'created_at' => $m->created_at->format('H:i'),
        ])->toArray();
    }

    private function getOrCreateSession(string $clienteId): ChatContexto
    {
        if ($this->sessionId) {
            $existing = ChatContexto::find($this->sessionId);
            if ($existing) {
                return $existing;
            }
        }

        return ChatContexto::create([
            'cliente_id' => $clienteId,
            'mensagens' => [],
        ]);
    }

    public function render()
    {
        return view('livewire.chat.chat-window')
            ->layout('layouts.app');
    }
}
