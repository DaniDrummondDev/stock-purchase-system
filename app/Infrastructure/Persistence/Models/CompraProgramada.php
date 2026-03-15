<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class CompraProgramada extends Model implements AuditableContract
{
    use Auditable, HasUuids;

    protected $table = 'compras_programadas';

    protected $fillable = [
        'data_execucao',
        'status',
        'valor_total',
    ];

    protected function casts(): array
    {
        return [
            'data_execucao' => 'date',
            'valor_total' => 'decimal:2',
        ];
    }

    public function participantes(): HasMany
    {
        return $this->hasMany(CompraParticipante::class, 'compra_id');
    }

    public function distribuicoes(): HasMany
    {
        return $this->hasMany(CompraDistribuicao::class, 'compra_id');
    }
}
