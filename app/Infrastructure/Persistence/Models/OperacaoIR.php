<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperacaoIR extends Model
{
    use HasUuids;

    protected $table = 'operacoes_ir';

    protected $fillable = [
        'cliente_id',
        'tipo',
        'ticker',
        'valor_operacao',
        'imposto',
        'mes_referencia',
        'publicado_kafka',
    ];

    protected function casts(): array
    {
        return [
            'valor_operacao' => 'decimal:2',
            'imposto' => 'decimal:2',
            'publicado_kafka' => 'boolean',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function scopeDedoDuro($query)
    {
        return $query->where('tipo', 'dedo_duro');
    }

    public function scopeVenda($query)
    {
        return $query->where('tipo', 'venda');
    }

    public function scopeMes($query, string $mesReferencia)
    {
        return $query->where('mes_referencia', $mesReferencia);
    }
}
