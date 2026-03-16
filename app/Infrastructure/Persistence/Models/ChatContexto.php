<?php

namespace App\Infrastructure\Persistence\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class ChatContexto extends Model implements AuditableContract
{
    use Auditable;
    use HasUuids;

    protected $table = 'chat_contextos';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'mensagens' => 'array',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cliente_id');
    }

    public function appendMessage(string $role, string $content): void
    {
        $mensagens = $this->mensagens ?? [];

        $mensagens[] = [
            'role' => $role,
            'content' => $content,
            'timestamp' => now()->toIso8601String(),
        ];

        if (count($mensagens) > 50) {
            $mensagens = array_slice($mensagens, -50);
        }

        $this->mensagens = $mensagens;
    }
}
