<?php

declare(strict_types=1);

namespace App\Presentation\Livewire\Notifications;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NotificationBell extends Component
{
    public int $unreadCount = 0;

    public array $recentNotifications = [];

    public function mount(): void
    {
        $this->loadNotifications();
    }

    public function markAsRead(string $notificationId): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $notification = $user->unreadNotifications->firstWhere('id', $notificationId);
        if ($notification) {
            $notification->markAsRead();
        }

        $this->loadNotifications();
    }

    public function markAllRead(): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $user->unreadNotifications->markAsRead();
        $this->loadNotifications();
    }

    private function loadNotifications(): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $this->unreadCount = $user->unreadNotifications->count();

        $this->recentNotifications = $user->unreadNotifications
            ->take(5)
            ->map(fn ($n) => [
                'id' => $n->id,
                'title' => $n->data['title'] ?? 'Notificação',
                'summary' => $n->data['summary'] ?? '',
                'priority' => $n->data['priority'] ?? 'normal',
                'created_at' => $n->created_at->diffForHumans(),
            ])
            ->all();
    }

    public function render()
    {
        return view('livewire.notifications.notification-bell');
    }
}
