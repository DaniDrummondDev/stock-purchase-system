<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SecurityAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        private string $title,
        private string $message,
        private string $severity = 'high',
        private array $details = [],
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("[SPS Security] {$this->title}")
            ->greeting("Alerta de Segurança: {$this->severity}")
            ->line($this->message);

        foreach ($this->details as $key => $value) {
            $mail->line("{$key}: {$value}");
        }

        $mail->action('Ver Dashboard de Segurança', url('/admin/security'));

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'severity' => $this->severity,
            'details' => $this->details,
        ];
    }
}
