<?php

declare(strict_types=1);

use App\Infrastructure\AI\AiConfigResolver;
use App\Infrastructure\AI\Services\EmbeddingService;
use Laravel\Ai\Embeddings;

it('embed returns an array', function () {
    Embeddings::fake();

    $configResolver = Mockery::mock(AiConfigResolver::class);
    $configResolver->shouldReceive('resolveProviderName')
        ->with('embeddings')
        ->andReturn('voyageai');

    $service = new EmbeddingService($configResolver);

    $result = $service->embed('PETR4 | Fechamento: 35.50');

    expect($result)->toBeArray()
        ->and($result)->not->toBeEmpty();
});

it('embedBatch with multiple texts returns correct count', function () {
    Embeddings::fake();

    $configResolver = Mockery::mock(AiConfigResolver::class);
    $configResolver->shouldReceive('resolveProviderName')
        ->with('embeddings')
        ->andReturn('voyageai');

    $service = new EmbeddingService($configResolver);

    $texts = [
        'PETR4 | Fechamento: 35.50',
        'VALE3 | Fechamento: 68.90',
        'ITUB4 | Fechamento: 30.00',
    ];

    $result = $service->embedBatch($texts);

    expect($result)->toBeArray()
        ->and($result)->toHaveCount(3);
});
