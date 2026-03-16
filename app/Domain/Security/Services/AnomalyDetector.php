<?php

namespace App\Domain\Security\Services;

use App\Infrastructure\Persistence\Models\SecurityEvent;
use App\Models\User;
use Illuminate\Http\Request;

class AnomalyDetector
{
    public function __construct(
        private SecurityEventLogger $logger,
    ) {}

    public function checkLogin(User $user, Request $request): array
    {
        $anomalies = [];

        if ($this->isNewIp($user, $request->ip())) {
            $anomalies[] = 'new_ip';
            $this->logger->log('suspicious_activity', 'medium', (string) $user->id, "user:{$user->id}", [
                'type' => 'new_ip',
                'ip' => $request->ip(),
            ], $request);
        }

        if ($this->isOffHours()) {
            $anomalies[] = 'off_hours';
            $this->logger->log('suspicious_activity', 'low', (string) $user->id, "user:{$user->id}", [
                'type' => 'off_hours',
                'hour' => now()->format('H:i'),
            ], $request);
        }

        return $anomalies;
    }

    public function isNewIp(User $user, string $ip): bool
    {
        $recentIps = SecurityEvent::where('user_id', $user->id)
            ->where('event_type', 'login')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->pluck('ip_address')
            ->unique()
            ->all();

        if (empty($recentIps)) {
            return false; // First login, not anomalous
        }

        return ! in_array($ip, $recentIps, true);
    }

    public function isOffHours(): bool
    {
        $timezone = config('app.timezone', 'America/Sao_Paulo');
        $hour = (int) now()->timezone($timezone)->format('H');
        $start = (int) config('security.anomaly.off_hours_start', 0);
        $end = (int) config('security.anomaly.off_hours_end', 6);

        return $hour >= $start && $hour < $end;
    }
}
