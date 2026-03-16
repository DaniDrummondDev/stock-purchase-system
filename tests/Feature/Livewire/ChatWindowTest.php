<?php

declare(strict_types=1);

use App\Presentation\Livewire\Chat\ChatWindow;
use Livewire\Livewire;

it('can render chat window component', function () {
    Livewire::test(ChatWindow::class)
        ->assertStatus(200)
        ->assertSee('Assistente Financeiro');
});

it('starts with empty messages', function () {
    Livewire::test(ChatWindow::class)
        ->assertSet('messages', [])
        ->assertSet('loading', false);
});
