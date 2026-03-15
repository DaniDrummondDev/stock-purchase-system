<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Custodia extends Model implements AuditableContract
{
    use Auditable, HasUuids;

    protected $table = 'custodias';

    protected $fillable = [
        'cliente_id',
        'ticker',
        'quantidade',
        'preco_medio',
    ];

    protected function casts(): array
    {
        return [
            'quantidade' => 'integer',
            'preco_medio' => 'decimal:2',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }
}
