<?php

return [
    'brokers' => env('KAFKA_BROKERS', 'localhost:9092'),

    'topics' => [
        'ir_dedo_duro' => 'ir-dedo-duro',
        'ir_venda' => 'ir-venda',
        'alertas_risco' => 'alertas-risco',
        'compra_executada' => 'compra-executada',
    ],

    'producer' => [
        'required_acks' => 1,
        'timeout_ms' => 5000,
        'retry_count' => 3,
    ],

    'consumer' => [
        'group_id' => env('KAFKA_CONSUMER_GROUP', 'stock-purchase-system'),
        'auto_offset_reset' => 'earliest',
    ],
];
