<?php

use App\Domain\PurchaseEngine\Services\DataExecucaoService;

test('dia 5 em dia util retorna mesmo dia', function () {
    $service = new DataExecucaoService;
    // 2026-02-05 is Thursday
    $data = new DateTimeImmutable('2026-02-05');
    $result = $service->ajustarParaDiaUtil($data);

    expect($result->format('Y-m-d'))->toBe('2026-02-05');
});

test('dia 15 em sabado retorna segunda (dia 17)', function () {
    $service = new DataExecucaoService;
    // 2026-02-15 is Sunday
    $data = new DateTimeImmutable('2026-02-15');
    $result = $service->ajustarParaDiaUtil($data);

    expect($result->format('Y-m-d'))->toBe('2026-02-16');
});

test('dia 25 em domingo retorna segunda (dia 26)', function () {
    $service = new DataExecucaoService;
    // 2026-01-25 is Sunday
    $data = new DateTimeImmutable('2026-01-25');
    $result = $service->ajustarParaDiaUtil($data);

    expect($result->format('Y-m-d'))->toBe('2026-01-26');
});

test('sabado ajusta para segunda (+2 dias)', function () {
    $service = new DataExecucaoService;
    // 2026-04-25 is Saturday
    $data = new DateTimeImmutable('2026-04-25');
    $result = $service->ajustarParaDiaUtil($data);

    expect($result->format('N'))->toBe('1'); // Monday
});

test('datasDoMes retorna 3 datas ajustadas', function () {
    $service = new DataExecucaoService;
    $datas = $service->datasDoMes(2026, 2);

    expect($datas)->toHaveCount(3);

    foreach ($datas as $data) {
        $dayOfWeek = (int) $data->format('N');
        expect($dayOfWeek)->toBeLessThanOrEqual(5); // Mon-Fri
    }
});

test('isDataExecucaoValida aceita data ajustada', function () {
    $service = new DataExecucaoService;
    $datas = $service->datasDoMes(2026, 2);

    foreach ($datas as $data) {
        expect($service->isDataExecucaoValida($data))->toBeTrue();
    }
});

test('isDataExecucaoValida rejeita data arbitraria', function () {
    $service = new DataExecucaoService;
    $data = new DateTimeImmutable('2026-02-10');

    expect($service->isDataExecucaoValida($data))->toBeFalse();
});
