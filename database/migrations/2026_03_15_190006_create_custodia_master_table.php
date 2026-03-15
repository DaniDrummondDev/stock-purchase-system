<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custodia_master', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('ticker', 12)->unique();
            $table->integer('quantidade')->default(0);
            $table->decimal('preco_medio', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custodia_master');
    }
};
