<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Cliente extends Model implements AuditableContract
{
    use Auditable, HasUuids;

    protected $table = 'clientes';

    protected $fillable = [
        'nome',
        'cpf',
        'email',
        'valor_mensal',
        'status',
        'valor_total_investido',
    ];

    protected function casts(): array
    {
        return [
            'valor_mensal' => 'decimal:2',
            'valor_total_investido' => 'decimal:2',
        ];
    }

    public function contaGrafica(): HasOne
    {
        return $this->hasOne(ContaGrafica::class, 'cliente_id');
    }

    public function custodias(): HasMany
    {
        return $this->hasMany(Custodia::class, 'cliente_id');
    }

    public function scopeAtivos($query)
    {
        return $query->where('status', 'ativo');
    }
}
