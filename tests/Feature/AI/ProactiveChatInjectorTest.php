<?php

declare(strict_types=1);

use App\Infrastructure\AI\Notifications\ProactiveChatInjector;

it('can be instantiated', function () {
    $injector = app(ProactiveChatInjector::class);
    expect($injector)->toBeInstanceOf(ProactiveChatInjector::class);
});
