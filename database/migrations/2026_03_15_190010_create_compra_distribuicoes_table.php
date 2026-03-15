<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compra_distribuicoes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('compra_id');
            $table->uuid('cliente_id');
            $table->string('ticker', 12);
            $table->integer('quantidade');
            $table->decimal('valor', 12, 2);
            $table->decimal('preco_unitario', 12, 2);
            $table->enum('tipo_lote', ['padrao', 'fracionario']);
            $table->date('data_pregao');
            $table->timestamps();

            $table->foreign('compra_id')->references('id')->on('compras_programadas')->onDelete('cascade');
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');
            $table->index(['cliente_id', 'ticker']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compra_distribuicoes');
    }
};
