<?php

declare(strict_types=1);

namespace App\Infrastructure\AI\Notifications;

use App\Domain\AI\Contracts\AgentResult;
use App\Infrastructure\Persistence\Models\AlertPreference;
use App\Models\User;
use App\Notifications\AgentResultNotification;

final class AgentNotificationDispatcher
{
    public function __construct(
        private readonly NotificationPrioritizer $prioritizer,
    ) {}

    public function dispatch(AgentResult $result, string $clienteId, string $triggerType): void
    {
        // Check preferences
        $preference = AlertPreference::forCliente($clienteId)
            ->forTrigger($triggerType)
            ->first();

        // If preference exists and is disabled, skip
        if ($preference && ! $preference->enabled) {
            return;
        }

        $priority = $this->prioritizer->classify($result, $triggerType);

        // Resolve channels
        $channels = $this->resolveChannels($preference, $priority);

        // Find the user
        $user = User::where('cliente_id', $clienteId)->first();
        if (! $user) {
            return;
        }

        // Send notification
        $user->notify(new AgentResultNotification($result, $triggerType, $priority, $channels));
    }

    private function resolveChannels(?AlertPreference $preference, NotificationPriority $priority): array
    {
        if ($preference) {
            $configured = $preference->channels;
        } else {
            $configured = ['in_app'];
        }

        // Critical always gets all configured channels
        if ($priority === NotificationPriority::Critical) {
            return array_unique(array_merge($configured, ['in_app']));
        }

        // Normal: in_app always, email only if configured
        if ($priority === NotificationPriority::Normal) {
            return array_intersect($configured, ['in_app', 'email']);
        }

        // Low: in_app only
        return ['in_app'];
    }
}
