<?php

declare(strict_types=1);

use App\Infrastructure\AI\Notifications\AgentNotificationDispatcher;
use App\Infrastructure\AI\Notifications\NotificationPrioritizer;

it('can be instantiated with prioritizer', function () {
    $dispatcher = new AgentNotificationDispatcher(new NotificationPrioritizer);
    expect($dispatcher)->toBeInstanceOf(AgentNotificationDispatcher::class);
});
