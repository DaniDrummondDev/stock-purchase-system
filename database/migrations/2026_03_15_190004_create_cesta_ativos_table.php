<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cesta_ativos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('cesta_id');
            $table->string('ticker', 12);
            $table->decimal('percentual', 5, 2);
            $table->timestamps();

            $table->foreign('cesta_id')->references('id')->on('cestas')->onDelete('cascade');
            $table->unique(['cesta_id', 'ticker']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cesta_ativos');
    }
};
