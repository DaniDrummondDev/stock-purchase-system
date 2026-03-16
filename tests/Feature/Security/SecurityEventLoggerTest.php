<?php

use App\Domain\Security\Services\SecurityEventLogger;
use App\Infrastructure\Persistence\Models\SecurityEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

test('logs login success event', function () {
    $logger = new SecurityEventLogger;
    $request = Request::create('/login', 'POST');

    $userId = (string) Str::uuid();
    $logger->loginSuccess($userId, $request);

    $this->assertDatabaseHas('security_events', [
        'event_type' => 'login',
        'severity' => 'medium',
        'user_id' => $userId,
    ]);
});

test('logs login failed event', function () {
    $logger = new SecurityEventLogger;
    $request = Request::create('/login', 'POST');

    $logger->loginFailed('test@test.com', $request);

    $this->assertDatabaseHas('security_events', [
        'event_type' => 'login_failed',
        'severity' => 'high',
    ]);
});

test('logs ip blocked event', function () {
    $logger = new SecurityEventLogger;

    $logger->ipBlocked('192.168.1.100', 'brute_force');

    $this->assertDatabaseHas('security_events', [
        'event_type' => 'ip_blocked',
        'severity' => 'critical',
    ]);
});

test('security event stores details as json', function () {
    $logger = new SecurityEventLogger;

    $userId = (string) Str::uuid();
    $logger->log('test_event', 'low', $userId, 'test', ['key' => 'value']);

    $event = SecurityEvent::where('event_type', 'test_event')->first();

    expect($event->details)->toBe(['key' => 'value']);
});
