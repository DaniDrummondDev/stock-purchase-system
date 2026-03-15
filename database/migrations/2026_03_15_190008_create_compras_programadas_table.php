<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compras_programadas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('data_execucao');
            $table->enum('status', ['pendente', 'processando', 'concluida', 'erro'])->default('pendente');
            $table->decimal('valor_total', 15, 2)->default(0);
            $table->timestamps();

            $table->unique('data_execucao');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compras_programadas');
    }
};
