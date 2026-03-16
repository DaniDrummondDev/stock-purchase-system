<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DataProviderConfig extends Model
{
    use HasUuids;

    protected $table = 'data_provider_configs';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'api_key' => 'encrypted',
            'enabled' => 'boolean',
        ];
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('enabled', true);
    }

    public function scopeByProvider(Builder $query, string $name): Builder
    {
        return $query->where('provider_name', $name);
    }
}
