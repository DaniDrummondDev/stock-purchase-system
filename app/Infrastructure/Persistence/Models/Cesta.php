<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Cesta extends Model implements AuditableContract
{
    use Auditable, HasUuids;

    protected $table = 'cestas';

    protected $fillable = [
        'nome',
        'ativo',
        'data_desativacao',
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'data_desativacao' => 'datetime',
        ];
    }

    public function ativos(): HasMany
    {
        return $this->hasMany(CestaAtivo::class, 'cesta_id');
    }

    public function scopeAtiva($query)
    {
        return $query->where('ativo', true);
    }
}
