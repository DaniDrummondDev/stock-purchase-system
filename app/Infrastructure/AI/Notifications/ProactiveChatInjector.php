<?php

declare(strict_types=1);

namespace App\Infrastructure\AI\Notifications;

use App\Infrastructure\Persistence\Models\AlertPreference;
use App\Infrastructure\Persistence\Models\ChatContexto;
use App\Infrastructure\Persistence\Models\ChatMessage;
use Illuminate\Contracts\Cache\Repository as Cache;

final class ProactiveChatInjector
{
    private const MAX_DAILY = 3;

    public function __construct(
        private readonly Cache $cache,
    ) {}

    public function inject(string $clienteId, string $message): bool
    {
        // Check opt-in
        $preference = AlertPreference::forCliente($clienteId)
            ->forTrigger('proactive_chat')
            ->enabled()
            ->first();

        if (! $preference) {
            return false;
        }

        // Check daily limit
        $key = "proactive_chat:{$clienteId}:".now()->format('Y-m-d');
        $count = (int) $this->cache->get($key, 0);

        if ($count >= self::MAX_DAILY) {
            return false;
        }

        // Get or create latest session
        $session = ChatContexto::where('cliente_id', $clienteId)
            ->orderBy('updated_at', 'desc')
            ->first();

        if (! $session) {
            $session = ChatContexto::create([
                'cliente_id' => $clienteId,
                'mensagens' => [],
            ]);
        }

        // Inject system message
        ChatMessage::create([
            'session_id' => $session->id,
            'cliente_id' => $clienteId,
            'role' => 'system',
            'content' => $message,
            'created_at' => now(),
        ]);

        // Increment daily counter
        $this->cache->put($key, $count + 1, now()->endOfDay());

        return true;
    }
}
