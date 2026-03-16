<?php

declare(strict_types=1);

namespace App\Presentation\Livewire\Notifications;

use App\Infrastructure\Persistence\Models\AlertPreference;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AlertPreferences extends Component
{
    public array $preferences = [];

    public string $message = '';

    public string $messageType = '';

    public function mount(): void
    {
        $this->loadPreferences();
    }

    public function toggleTrigger(string $triggerId): void
    {
        $preference = AlertPreference::find($triggerId);
        if (! $preference) {
            return;
        }

        $preference->update(['enabled' => ! $preference->enabled]);

        $this->message = 'Preferência atualizada com sucesso.';
        $this->messageType = 'success';
        $this->loadPreferences();
    }

    public function updateChannels(string $triggerId, array $channels): void
    {
        $preference = AlertPreference::find($triggerId);
        if (! $preference) {
            return;
        }

        // in_app is always required
        if (! in_array('in_app', $channels)) {
            $channels[] = 'in_app';
        }

        $preference->update(['channels' => $channels]);

        $this->message = 'Canais atualizados com sucesso.';
        $this->messageType = 'success';
        $this->loadPreferences();
    }

    private function loadPreferences(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->cliente_id) {
            return;
        }

        $existing = AlertPreference::forCliente($user->cliente_id)->get();

        // Create defaults if none exist
        if ($existing->isEmpty()) {
            foreach (AlertPreference::TRIGGER_TYPES as $type => $label) {
                AlertPreference::create([
                    'cliente_id' => $user->cliente_id,
                    'trigger_type' => $type,
                    'enabled' => true,
                    'channels' => ['in_app'],
                ]);
            }

            $existing = AlertPreference::forCliente($user->cliente_id)->get();
        }

        $this->preferences = $existing->map(fn (AlertPreference $pref) => [
            'id' => $pref->id,
            'trigger_type' => $pref->trigger_type,
            'label' => AlertPreference::TRIGGER_TYPES[$pref->trigger_type] ?? $pref->trigger_type,
            'enabled' => $pref->enabled,
            'channels' => $pref->channels ?? ['in_app'],
        ])->all();
    }

    public function render()
    {
        return view('livewire.notifications.alert-preferences')
            ->layout('layouts.app');
    }
}
