<?php

declare(strict_types=1);

use App\Presentation\Livewire\Notifications\AlertPreferences;
use Livewire\Livewire;

it('can render alert preferences component', function () {
    Livewire::test(AlertPreferences::class)
        ->assertStatus(200)
        ->assertSee('Preferências de Alertas');
});
