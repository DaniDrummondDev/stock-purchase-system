<?php

declare(strict_types=1);

namespace App\Domain\AI\Contracts;

enum TriggerType: string
{
    case Chat = 'chat';
    case Scheduled = 'scheduled';
    case Event = 'event';
}
