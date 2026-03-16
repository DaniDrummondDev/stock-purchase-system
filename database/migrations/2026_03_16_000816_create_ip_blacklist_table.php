<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ip_blacklist', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('ip_address', 45);
            $table->string('reason', 100);
            $table->timestamp('blocked_until')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->unique('ip_address');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ip_blacklist');
    }
};
