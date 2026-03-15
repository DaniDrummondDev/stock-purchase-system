<?php

use App\Domain\Tax\Services\DedoDuroService;

test('calcula IR dedo-duro a 0.005%', function () {
    $service = new DedoDuroService;

    // R$ 280.00 × 0.005% = R$ 0.014 → R$ 0.01
    expect($service->calcular(280.00))->toBe(0.01);
});

test('calcula IR para valor alto', function () {
    $service = new DedoDuroService;

    // R$ 10.000 × 0.005% = R$ 0.50
    expect($service->calcular(10000.00))->toBe(0.50);
});

test('calcula IR para valor zero', function () {
    $service = new DedoDuroService;
    expect($service->calcular(0))->toBe(0.0);
});

test('retorna aliquota correta', function () {
    $service = new DedoDuroService;
    expect($service->aliquota())->toBe(0.00005);
});

test('arredonda para 2 casas decimais', function () {
    $service = new DedoDuroService;

    // R$ 248.00 × 0.005% = R$ 0.0124 → R$ 0.01
    expect($service->calcular(248.00))->toBe(0.01);
});
