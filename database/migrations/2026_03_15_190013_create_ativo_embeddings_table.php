<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ativo_embeddings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('ticker', 12);
            $table->jsonb('metadata')->nullable();
            $table->date('data_referencia');
            $table->timestamps();

            $table->index('ticker');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE ativo_embeddings ADD COLUMN embedding vector(1024)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ativo_embeddings');
    }
};
