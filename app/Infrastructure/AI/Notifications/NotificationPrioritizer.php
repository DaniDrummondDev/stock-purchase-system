<?php

declare(strict_types=1);

namespace App\Infrastructure\AI\Notifications;

use App\Domain\AI\Contracts\AgentResult;

final class NotificationPrioritizer
{
    public function classify(AgentResult $result, string $triggerType): NotificationPriority
    {
        // Critical: risk score >= 0.8, or confidence is very low on critical triggers
        if (isset($result->data['score']) && (float) $result->data['score'] >= 0.8) {
            return NotificationPriority::Critical;
        }

        if (isset($result->data['totalPL']) && (float) $result->data['totalPL'] < -10000) {
            return NotificationPriority::Critical;
        }

        // Critical triggers
        if (in_array($triggerType, ['daily_risk_scan', 'tax_alert'])) {
            if ($result->confidence >= 0.8) {
                return NotificationPriority::Normal;
            }

            return NotificationPriority::Critical;
        }

        // Normal triggers
        if (in_array($triggerType, ['weekly_report', 'rebalancing_check', 'compra_executada', 'rebalanceamento', 'valor_mensal_alterado'])) {
            return NotificationPriority::Normal;
        }

        // Low triggers
        return NotificationPriority::Low;
    }
}
