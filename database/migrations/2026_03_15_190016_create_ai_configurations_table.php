<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_configurations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('scope', ['global', 'user'])->default('user');
            $table->uuid('user_id')->nullable();
            $table->string('provider', 50);
            $table->string('purpose', 30);
            $table->text('api_key');
            $table->jsonb('settings')->default('{}');
            $table->boolean('is_active')->default(true);
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('clientes')->onDelete('cascade');
            $table->unique(['scope', 'user_id', 'purpose']);
            $table->index(['scope', 'purpose', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_configurations');
    }
};
