<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;

class CestaAtivo extends Model implements \OwenIt\Auditing\Contracts\Auditable
{
    use Auditable;
    use HasUuids;

    protected $table = 'cesta_ativos';

    protected $fillable = [
        'cesta_id',
        'ticker',
        'percentual',
    ];

    protected function casts(): array
    {
        return [
            'percentual' => 'decimal:2',
        ];
    }

    public function cesta(): BelongsTo
    {
        return $this->belongsTo(Cesta::class, 'cesta_id');
    }
}
