<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class AtivoEmbedding extends Model implements AuditableContract
{
    use Auditable;

    protected $table = 'ativo_embeddings';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'data_referencia' => 'date',
        ];
    }

    public function scopeByTicker(Builder $query, string $ticker): Builder
    {
        return $query->where('ticker', $ticker);
    }

    public function scopeByDate(Builder $query, string $date): Builder
    {
        return $query->where('data_referencia', $date);
    }

    public static function similarTo(array $vector, int $limit = 10, array $excludeTickers = []): Collection
    {
        $vectorStr = '['.implode(',', $vector).']';

        $query = static::select('*')
            ->selectRaw('embedding <=> ? AS distance', [$vectorStr])
            ->whereRaw('data_referencia = (SELECT MAX(data_referencia) FROM ativo_embeddings)');

        if (! empty($excludeTickers)) {
            $query->whereNotIn('ticker', $excludeTickers);
        }

        return $query->orderBy('distance')->limit($limit)->get();
    }
}
