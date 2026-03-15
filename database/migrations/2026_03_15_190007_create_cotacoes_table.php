<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotacoes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('ticker', 12);
            $table->date('data_pregao');
            $table->decimal('preco_fechamento', 12, 2);
            $table->decimal('preco_abertura', 12, 2);
            $table->decimal('preco_maximo', 12, 2);
            $table->decimal('preco_minimo', 12, 2);
            $table->enum('tipo_mercado', ['padrao', 'fracionario']);
            $table->string('cod_bdi', 2);
            $table->decimal('volume', 18, 2)->default(0);
            $table->timestamps();

            $table->unique(['ticker', 'data_pregao', 'tipo_mercado']);
            $table->index(['ticker', 'data_pregao']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotacoes');
    }
};
