<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SecurityEvent extends Model
{
    use HasUuids;

    protected $table = 'security_events';

    public $timestamps = false;

    protected $fillable = [
        'event_type',
        'severity',
        'user_id',
        'ip_address',
        'user_agent',
        'request_id',
        'resource',
        'details',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function scopeSeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeEventType($query, string $type)
    {
        return $query->where('event_type', $type);
    }
}
