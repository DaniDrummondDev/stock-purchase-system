<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class AnaliseRiscoCache extends Model implements AuditableContract
{
    use Auditable;
    use HasUuids;

    protected $table = 'analise_risco_cache';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'alertas' => 'array',
            'score_risco' => 'decimal:2',
            'valid_until' => 'datetime',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function scopeForCliente(Builder $query, string $clienteId): Builder
    {
        return $query->where('cliente_id', $clienteId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('valid_until', '>', now());
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('valid_until', '<=', now());
    }
}
