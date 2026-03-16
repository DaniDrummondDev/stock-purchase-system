<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\Models\ChatMessage;

it('chat messages table exists after migration', function () {
    expect(Schema::hasTable('chat_messages'))->toBeTrue();
});

it('chat message model can be created', function () {
    // Just check the model class exists and is instantiable
    $message = new ChatMessage;

    expect($message)->toBeInstanceOf(ChatMessage::class)
        ->and($message->getTable())->toBe('chat_messages');
});
