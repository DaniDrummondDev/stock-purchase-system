<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompraDistribuicao extends Model
{
    use HasUuids;

    protected $table = 'compra_distribuicoes';

    protected $fillable = [
        'compra_id',
        'cliente_id',
        'ticker',
        'quantidade',
        'valor',
        'preco_unitario',
        'tipo_lote',
        'data_pregao',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
            'preco_unitario' => 'decimal:2',
            'data_pregao' => 'date',
        ];
    }

    public function compra(): BelongsTo
    {
        return $this->belongsTo(CompraProgramada::class, 'compra_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }
}
