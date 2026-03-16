<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ativo_embeddings', function (Blueprint $table) {
            $table->unique(['ticker', 'data_referencia'], 'ativo_embeddings_ticker_data_referencia_unique');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX ativo_embeddings_hnsw_idx ON ativo_embeddings USING hnsw (embedding vector_cosine_ops)');
        }
    }

    public function down(): void
    {
        Schema::table('ativo_embeddings', function (Blueprint $table) {
            $table->dropUnique('ativo_embeddings_ticker_data_referencia_unique');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS ativo_embeddings_hnsw_idx');
        }
    }
};
