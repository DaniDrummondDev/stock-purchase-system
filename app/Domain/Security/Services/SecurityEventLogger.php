<?php

namespace App\Domain\Security\Services;

use App\Infrastructure\Persistence\Models\SecurityEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SecurityEventLogger
{
    public function log(
        string $eventType,
        string $severity,
        ?string $userId = null,
        ?string $resource = null,
        array $details = [],
        ?Request $request = null,
    ): SecurityEvent {
        return SecurityEvent::create([
            'event_type' => $eventType,
            'severity' => $severity,
            'user_id' => $userId,
            'ip_address' => $request?->ip() ?? '0.0.0.0',
            'user_agent' => $request ? substr((string) $request->userAgent(), 0, 500) : null,
            'request_id' => (string) Str::uuid(),
            'resource' => $resource,
            'details' => $details,
            'created_at' => now(),
        ]);
    }

    public function loginSuccess(string $userId, Request $request): void
    {
        $this->log('login', 'medium', $userId, "user:{$userId}", [], $request);
    }

    public function loginFailed(string $email, Request $request): void
    {
        $this->log('login_failed', 'high', null, "email:{$email}", [
            'email' => $email,
        ], $request);
    }

    public function logout(string $userId, Request $request): void
    {
        $this->log('logout', 'medium', $userId, "user:{$userId}", [], $request);
    }

    public function accessDenied(string $userId, string $permission, Request $request): void
    {
        $this->log('access_denied', 'high', $userId, "permission:{$permission}", [
            'permission' => $permission,
        ], $request);
    }

    public function ipBlocked(string $ip, string $reason): void
    {
        $this->log('ip_blocked', 'critical', null, "ip:{$ip}", [
            'reason' => $reason,
        ]);
    }

    public function passwordChanged(string $userId, Request $request): void
    {
        $this->log('password_changed', 'medium', $userId, "user:{$userId}", [], $request);
    }

    public function permissionChanged(string $userId, array $changes, Request $request): void
    {
        $this->log('permission_changed', 'critical', $userId, "user:{$userId}", $changes, $request);
    }
}
