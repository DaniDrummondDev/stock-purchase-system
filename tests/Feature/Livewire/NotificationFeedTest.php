<?php

declare(strict_types=1);

use App\Presentation\Livewire\Notifications\NotificationFeed;
use Livewire\Livewire;

it('can render notification feed component', function () {
    Livewire::test(NotificationFeed::class)
        ->assertStatus(200)
        ->assertSee('Notificações');
});
