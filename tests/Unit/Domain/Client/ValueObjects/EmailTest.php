<?php

use App\Domain\Client\ValueObjects\Email;

test('cria email valido', function () {
    $email = new Email('joao@email.com');
    expect($email->value())->toBe('joao@email.com');
});

test('normaliza email para lowercase', function () {
    $email = new Email('Joao@Email.COM');
    expect($email->value())->toBe('joao@email.com');
});

test('rejeita email invalido', function () {
    new Email('invalido');
})->throws(InvalidArgumentException::class);

test('rejeita email vazio', function () {
    new Email('');
})->throws(InvalidArgumentException::class);

test('compara dois emails iguais', function () {
    $email1 = new Email('joao@email.com');
    $email2 = new Email('JOAO@email.com');
    expect($email1->equals($email2))->toBeTrue();
});
