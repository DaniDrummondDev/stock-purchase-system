<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_provider_configs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('provider_name', 100)->unique();
            $table->boolean('enabled')->default(true);
            $table->jsonb('settings')->default('{}');
            $table->text('api_key')->nullable();
            $table->integer('priority')->default(0);
            $table->integer('rate_limit')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_provider_configs');
    }
};
