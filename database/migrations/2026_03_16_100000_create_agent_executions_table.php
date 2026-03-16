<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_executions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('cliente_id')->nullable();
            $table->string('agent_name', 100);
            $table->enum('trigger_type', ['chat', 'scheduled', 'event']);
            $table->jsonb('input_context');
            $table->jsonb('result_data')->nullable();
            $table->decimal('confidence', 3, 2)->nullable();
            $table->integer('execution_time_ms')->nullable();
            $table->jsonb('tokens_used')->nullable();
            $table->enum('status', ['pending', 'running', 'completed', 'failed', 'circuit_open'])->default('pending');
            $table->text('error_message')->nullable();
            $table->string('provider_used', 50)->nullable();
            $table->string('model_used', 100)->nullable();
            $table->timestamps();

            $table->index(['cliente_id', 'created_at']);
            $table->index(['agent_name', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_executions');
    }
};
