<?php

declare(strict_types=1);

namespace App\Infrastructure\Logging;

use Monolog\Formatter\JsonFormatter as MonologJsonFormatter;
use Monolog\LogRecord;

class JsonFormatter extends MonologJsonFormatter
{
    public function format(LogRecord $record): string
    {
        $data = [
            'timestamp' => $record->datetime->format('c'),
            'level' => $record->level->getName(),
            'message' => $record->message,
            'channel' => $record->channel,
            'context' => $record->context,
            'extra' => array_filter([
                'request_id' => $record->extra['request_id'] ?? null,
                'user_id' => $record->extra['user_id'] ?? null,
                'ip' => $record->extra['ip'] ?? null,
            ]),
        ];

        return $this->toJson($data)."\n";
    }
}
