<?php

namespace App\Notifications;

use App\Domain\AI\Contracts\AgentResult;
use App\Infrastructure\AI\Notifications\NotificationPriority;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AgentResultNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly AgentResult $agentResult,
        public readonly string $triggerType,
        public readonly NotificationPriority $priority,
        public readonly array $channels = ['in_app'],
    ) {}

    public function via(object $notifiable): array
    {
        $via = ['database']; // in_app always via database

        if (in_array('email', $this->channels)) {
            $via[] = 'mail';
        }

        return $via;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("[{$this->priority->label()}] Alerta do Sistema de Compra Programada")
            ->greeting("Olá {$notifiable->name},")
            ->line($this->agentResult->summary)
            ->line("Prioridade: {$this->priority->label()}")
            ->line('Confiança: '.round($this->agentResult->confidence * 100).'%')
            ->action('Ver Detalhes', url('/dashboard'))
            ->line('Este é um alerta automático do sistema.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'trigger_type' => $this->triggerType,
            'priority' => $this->priority->value,
            'summary' => $this->agentResult->summary,
            'confidence' => $this->agentResult->confidence,
            'data' => $this->agentResult->data,
        ];
    }
}
