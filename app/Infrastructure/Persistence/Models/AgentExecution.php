<?php

namespace App\Infrastructure\Persistence\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class AgentExecution extends Model implements AuditableContract
{
    use Auditable;
    use HasUuids;

    protected $table = 'agent_executions';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'input_context' => 'array',
            'result_data' => 'array',
            'tokens_used' => 'array',
            'confidence' => 'decimal:2',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cliente_id');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    public function scopeForAgent(Builder $query, string $agentName): Builder
    {
        return $query->where('agent_name', $agentName);
    }
}
