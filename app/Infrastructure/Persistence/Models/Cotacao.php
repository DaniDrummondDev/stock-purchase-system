<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Cotacao extends Model implements AuditableContract
{
    use Auditable;

    protected $table = 'cotacoes';

    protected $fillable = [
        'ticker',
        'data_pregao',
        'preco_fechamento',
        'preco_abertura',
        'preco_maximo',
        'preco_minimo',
        'tipo_mercado',
        'cod_bdi',
        'volume',
    ];

    protected function casts(): array
    {
        return [
            'data_pregao' => 'date',
            'preco_fechamento' => 'decimal:2',
            'preco_abertura' => 'decimal:2',
            'preco_maximo' => 'decimal:2',
            'preco_minimo' => 'decimal:2',
            'volume' => 'decimal:2',
        ];
    }
}
