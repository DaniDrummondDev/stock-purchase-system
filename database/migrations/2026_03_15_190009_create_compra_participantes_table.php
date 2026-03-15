<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compra_participantes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('compra_id');
            $table->uuid('cliente_id');
            $table->decimal('valor_aporte', 12, 2);
            $table->timestamps();

            $table->foreign('compra_id')->references('id')->on('compras_programadas')->onDelete('cascade');
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');
            $table->unique(['compra_id', 'cliente_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compra_participantes');
    }
};
