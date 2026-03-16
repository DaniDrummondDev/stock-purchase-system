<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class IpBlacklist extends Model
{
    use HasUuids;

    protected $table = 'ip_blacklist';

    protected $fillable = [
        'ip_address',
        'reason',
        'blocked_until',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'blocked_until' => 'datetime',
        ];
    }

    public function isActive(): bool
    {
        return $this->blocked_until === null || $this->blocked_until->isFuture();
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('blocked_until')
                ->orWhere('blocked_until', '>', now());
        });
    }
}
