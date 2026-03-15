<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operacoes_ir', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('cliente_id');
            $table->enum('tipo', ['dedo_duro', 'venda']);
            $table->string('ticker', 12);
            $table->decimal('valor_operacao', 12, 2);
            $table->decimal('imposto', 12, 2);
            $table->string('mes_referencia', 7);
            $table->boolean('publicado_kafka')->default(false);
            $table->timestamps();

            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');
            $table->index(['cliente_id', 'mes_referencia']);
            $table->index('tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operacoes_ir');
    }
};
