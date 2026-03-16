<?php

declare(strict_types=1);

use Illuminate\Support\Str;

test('POST /api/ai/recomendacao-cesta returns 404 when no active cesta exists', function () {
    $response = $this->postJson('/api/ai/recomendacao-cesta');

    $response->assertStatus(404)
        ->assertJson([
            'error' => 'CESTA_NAO_ENCONTRADA',
        ]);
});

test('POST /api/ai/chat returns 422 when cliente_id is missing', function () {
    $response = $this->postJson('/api/ai/chat', [
        'message' => 'Como está minha carteira?',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['cliente_id']);
});

test('POST /api/ai/chat returns 422 when message is missing', function () {
    $response = $this->postJson('/api/ai/chat', [
        'cliente_id' => Str::uuid()->toString(),
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['message']);
});

test('POST /api/ai/chat returns 422 when cliente_id does not exist in clientes table', function () {
    $nonExistentId = Str::uuid()->toString();

    $response = $this->postJson('/api/ai/chat', [
        'cliente_id' => $nonExistentId,
        'message' => 'Como está minha carteira?',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['cliente_id']);
});
