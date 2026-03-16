<?php

declare(strict_types=1);

use App\Domain\AI\ValueObjects\TickerEmbeddingData;

it('constructs with valid data', function () {
    $data = new TickerEmbeddingData(
        ticker: 'PETR4',
        precoFechamento: 35.50,
        precoAbertura: 34.80,
        precoMaximo: 36.00,
        precoMinimo: 34.50,
        volume: 15000000.0,
        variacao5d: 2.35,
        variacao20d: -1.80,
        volatilidade20d: 3.45,
        dataReferencia: '2026-03-15',
    );

    expect($data->ticker)->toBe('PETR4')
        ->and($data->precoFechamento)->toBe(35.50)
        ->and($data->precoAbertura)->toBe(34.80)
        ->and($data->precoMaximo)->toBe(36.00)
        ->and($data->precoMinimo)->toBe(34.50)
        ->and($data->volume)->toBe(15000000.0)
        ->and($data->variacao5d)->toBe(2.35)
        ->and($data->variacao20d)->toBe(-1.80)
        ->and($data->volatilidade20d)->toBe(3.45)
        ->and($data->dataReferencia)->toBe('2026-03-15');
});

it('toEmbeddingText returns formatted string containing ticker, prices, and variations', function () {
    $data = new TickerEmbeddingData(
        ticker: 'VALE3',
        precoFechamento: 68.90,
        precoAbertura: 67.50,
        precoMaximo: 69.20,
        precoMinimo: 67.00,
        volume: 20000000.0,
        variacao5d: 1.50,
        variacao20d: -3.20,
        volatilidade20d: 4.10,
        dataReferencia: '2026-03-14',
    );

    $text = $data->toEmbeddingText();

    expect($text)->toContain('VALE3')
        ->and($text)->toContain('Fechamento: 68.90')
        ->and($text)->toContain('Abertura: 67.50')
        ->and($text)->toContain('Max: 69.20')
        ->and($text)->toContain('Min: 67.00')
        ->and($text)->toContain('Variacao 5d: +1.50%')
        ->and($text)->toContain('Variacao 20d: -3.20%')
        ->and($text)->toContain('Volume medio: 20000000')
        ->and($text)->toContain('Volatilidade 20d: 4.10%');
});

it('has immutable readonly properties', function () {
    $data = new TickerEmbeddingData(
        ticker: 'ITUB4',
        precoFechamento: 30.00,
        precoAbertura: 29.50,
        precoMaximo: 30.50,
        precoMinimo: 29.00,
        volume: 10000000.0,
        variacao5d: 0.00,
        variacao20d: 0.00,
        volatilidade20d: 2.00,
        dataReferencia: '2026-03-15',
    );

    $reflection = new ReflectionClass($data);

    expect($reflection->isReadOnly())->toBeTrue();

    foreach ($reflection->getProperties() as $property) {
        expect($property->isReadOnly())->toBeTrue();
    }
});
