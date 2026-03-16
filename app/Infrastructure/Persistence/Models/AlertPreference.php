<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class AlertPreference extends Model implements AuditableContract
{
    use Auditable;
    use HasUuids;

    protected $table = 'alert_preferences';

    protected $guarded = [];

    public const TRIGGER_TYPES = [
        'daily_risk_scan' => 'Análise de Risco Diária',
        'market_briefing' => 'Briefing de Mercado',
        'weekly_report' => 'Relatório Semanal',
        'rebalancing_check' => 'Verificação de Rebalanceamento',
        'tax_alert' => 'Alerta de IR Mensal',
        'compra_executada' => 'Compra Executada',
        'rebalanceamento' => 'Rebalanceamento',
        'cliente_aderiu' => 'Boas-Vindas',
        'valor_mensal_alterado' => 'Alteração de Valor Mensal',
        'proactive_chat' => 'Chat Proativo',
    ];

    protected function casts(): array
    {
        return [
            'channels' => 'array',
            'threshold_config' => 'array',
            'enabled' => 'boolean',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cliente_id');
    }

    public function scopeForCliente(Builder $query, string $clienteId): Builder
    {
        return $query->where('cliente_id', $clienteId);
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('enabled', true);
    }

    public function scopeForTrigger(Builder $query, string $triggerType): Builder
    {
        return $query->where('trigger_type', $triggerType);
    }
}
