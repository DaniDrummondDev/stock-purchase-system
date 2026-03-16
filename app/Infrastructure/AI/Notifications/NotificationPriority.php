<?php

declare(strict_types=1);

namespace App\Infrastructure\AI\Notifications;

enum NotificationPriority: string
{
    case Critical = 'critical';
    case Normal = 'normal';
    case Low = 'low';

    public function label(): string
    {
        return match ($this) {
            self::Critical => 'Crítico',
            self::Normal => 'Normal',
            self::Low => 'Baixo',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Critical => 'red',
            self::Normal => 'yellow',
            self::Low => 'gray',
        };
    }
}
