<?php

namespace App\Presentation\Livewire\Admin;

use App\Infrastructure\Persistence\Models\IpBlacklist;
use App\Infrastructure\Persistence\Models\SecurityEvent;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SecurityDashboard extends Component
{
    public array $eventCounts = [];

    public array $recentEvents = [];

    public array $blockedIps = [];

    public array $lockedUsers = [];

    public function mount(): void
    {
        abort_unless(Auth::user()?->hasRole(['admin', 'auditor']), 403);

        $this->loadData();
    }

    public function unblockIp(string $id): void
    {
        IpBlacklist::where('id', $id)->delete();
        $this->loadData();
    }

    private function loadData(): void
    {
        // Event counts by severity
        $this->eventCounts = [
            'critical_24h' => SecurityEvent::severity('critical')->where('created_at', '>=', now()->subDay())->count(),
            'high_24h' => SecurityEvent::severity('high')->where('created_at', '>=', now()->subDay())->count(),
            'medium_7d' => SecurityEvent::severity('medium')->where('created_at', '>=', now()->subWeek())->count(),
            'total_30d' => SecurityEvent::where('created_at', '>=', now()->subMonth())->count(),
        ];

        // Recent events
        $this->recentEvents = SecurityEvent::orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(fn ($e) => [
                'id' => $e->id,
                'type' => $e->event_type,
                'severity' => $e->severity,
                'ip' => $e->ip_address,
                'user_id' => $e->user_id,
                'resource' => $e->resource,
                'date' => $e->created_at->format('d/m H:i:s'),
            ])
            ->all();

        // Blocked IPs
        $this->blockedIps = IpBlacklist::active()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($b) => [
                'id' => $b->id,
                'ip' => $b->ip_address,
                'reason' => $b->reason,
                'until' => $b->blocked_until?->format('d/m/Y H:i') ?? 'Permanente',
                'date' => $b->created_at->format('d/m/Y H:i'),
            ])
            ->all();

        // Locked users
        $this->lockedUsers = User::whereNotNull('locked_until')
            ->where('locked_until', '>', now())
            ->get()
            ->map(fn ($u) => [
                'name' => $u->name,
                'email' => $u->email,
                'locked_until' => $u->locked_until,
                'attempts' => $u->failed_login_attempts,
            ])
            ->all();
    }

    public function render()
    {
        return view('livewire.admin.security-dashboard')
            ->layout('layouts.app');
    }
}
