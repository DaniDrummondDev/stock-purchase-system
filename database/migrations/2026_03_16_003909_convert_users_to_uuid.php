<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Skip if already converted to UUID (idempotent)
        $columnType = DB::selectOne("SELECT data_type FROM information_schema.columns WHERE table_name = 'users' AND column_name = 'id'");

        if ($columnType && $columnType->data_type === 'uuid') {
            // Already converted — ensure cliente_id column exists
            if (! Schema::hasColumn('users', 'cliente_id')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->uuid('cliente_id')->nullable()->after('role');
                    $table->index('cliente_id');
                });
            }

            return;
        }

        // Clear all data (safe: this only runs on first conversion)
        DB::table('model_has_roles')->truncate();
        DB::table('model_has_permissions')->truncate();
        DB::table('personal_access_tokens')->truncate();
        DB::table('sessions')->truncate();
        DB::table('security_events')->where('user_id', '!=', null)->delete();
        DB::table('users')->truncate();

        // 1. Drop FKs and constraints on sessions
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropColumn('user_id');
        });

        // 2. Convert users.id from bigint to uuid
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->uuid('id')->primary()->first();
            $table->uuid('cliente_id')->nullable()->after('role');

            $table->index('cliente_id');
        });

        // 3. Re-add sessions.user_id as uuid
        Schema::table('sessions', function (Blueprint $table) {
            $table->uuid('user_id')->nullable()->index();
        });

        // 4. Convert personal_access_tokens.tokenable_id to string (supports uuid morph)
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->string('tokenable_id', 36)->change();
        });

        // 5. Convert spatie permission model_id to string (supports uuid morph)
        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->string('model_id', 36)->change();
        });

        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->string('model_id', 36)->change();
        });
    }

    public function down(): void
    {
        // Reverse is destructive — would need to convert uuid back to bigint
        // Not practical; use fresh migration instead
    }
};
