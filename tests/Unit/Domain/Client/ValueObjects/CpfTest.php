<?php

use App\Domain\Client\ValueObjects\Cpf;

test('cria cpf valido', function () {
    $cpf = new Cpf('12345678909');
    expect($cpf->value())->toBe('12345678909');
});

test('cria cpf com formatacao', function () {
    $cpf = new Cpf('123.456.789-09');
    expect($cpf->value())->toBe('12345678909');
});

test('formata cpf corretamente', function () {
    $cpf = new Cpf('12345678909');
    expect($cpf->formatted())->toBe('123.456.789-09');
});

test('rejeita cpf com digitos iguais', function () {
    new Cpf('11111111111');
})->throws(InvalidArgumentException::class);

test('rejeita cpf com tamanho invalido', function () {
    new Cpf('123');
})->throws(InvalidArgumentException::class);

test('rejeita cpf com digito verificador invalido', function () {
    new Cpf('12345678900');
})->throws(InvalidArgumentException::class);

test('compara dois cpfs iguais', function () {
    $cpf1 = new Cpf('12345678909');
    $cpf2 = new Cpf('12345678909');
    expect($cpf1->equals($cpf2))->toBeTrue();
});

test('converte cpf para string', function () {
    $cpf = new Cpf('12345678909');
    expect((string) $cpf)->toBe('12345678909');
});
