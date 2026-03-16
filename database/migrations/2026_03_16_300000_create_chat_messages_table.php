<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('session_id');
            $table->uuid('cliente_id');
            $table->string('role', 20); // user, assistant, system
            $table->text('content');
            $table->jsonb('agent_results')->nullable();
            $table->integer('tokens_used')->nullable();
            $table->timestamp('created_at');

            $table->foreign('session_id')->references('id')->on('chat_contextos')->onDelete('cascade');
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');
            $table->index(['session_id', 'created_at']);
            $table->index(['cliente_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
