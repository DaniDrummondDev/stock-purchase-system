<?php

declare(strict_types=1);

namespace App\Presentation\Livewire\Notifications;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NotificationFeed extends Component
{
    public array $notifications = [];

    public string $filter = 'all';

    public function mount(): void
    {
        $this->loadNotifications();
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        $this->loadNotifications();
    }

    public function markAsRead(string $id): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $notification = $user->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();
        }

        $this->loadNotifications();
    }

    public function loadNotifications(): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $query = $user->notifications()->orderBy('created_at', 'desc');

        $all = $query->limit(50)->get();

        // Filter by priority from data->priority
        if ($this->filter !== 'all') {
            $all = $all->filter(fn ($n) => ($n->data['priority'] ?? 'normal') === $this->filter);
        }

        $this->notifications = $all->map(fn ($n) => [
            'id' => $n->id,
            'title' => $n->data['title'] ?? 'Notificação',
            'summary' => $n->data['summary'] ?? '',
            'priority' => $n->data['priority'] ?? 'normal',
            'read' => $n->read_at !== null,
            'created_at' => $n->created_at->diffForHumans(),
        ])->values()->all();
    }

    public function render()
    {
        return view('livewire.notifications.notification-feed')
            ->layout('layouts.app');
    }
}
