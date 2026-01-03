<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            // drop old unique
            $table->dropUnique('permissions_name_guard_name_unique');

            // add new unique including team_id
            $table->unique(['name', 'guard_name', 'team_id']);
        });
    }

    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropUnique(['name', 'guard_name', 'team_id']);
            $table->unique(['name', 'guard_name']);
        });
    }
};
