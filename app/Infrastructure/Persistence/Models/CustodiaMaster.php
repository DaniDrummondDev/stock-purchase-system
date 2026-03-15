<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class CustodiaMaster extends Model implements AuditableContract
{
    use Auditable, HasUuids;

    protected $table = 'custodia_master';

    protected $fillable = [
        'ticker',
        'quantidade',
        'preco_medio',
    ];

    protected function casts(): array
    {
        return [
            'preco_medio' => 'decimal:2',
        ];
    }
}
