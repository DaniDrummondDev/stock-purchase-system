<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompraParticipante extends Model
{
    use HasUuids;

    protected $table = 'compra_participantes';

    protected $fillable = [
        'compra_id',
        'cliente_id',
        'valor_aporte',
    ];

    protected function casts(): array
    {
        return [
            'valor_aporte' => 'decimal:2',
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
