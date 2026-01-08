<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('model_has_roles', function (Blueprint $table) {
            // Drop primary key first
            $table->dropPrimary();

            // Make team_id nullable
            $table->uuid('team_id')->nullable()->change();

            // Re-add UNIQUE constraint instead of PK
            $table->unique(
                ['role_id', 'model_id', 'model_type', 'team_id'],
                'model_has_roles_unique'
            );
        });

        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->dropPrimary();

            $table->uuid('team_id')->nullable()->change();

            $table->unique(
                ['permission_id', 'model_id', 'model_type', 'team_id'],
                'model_has_permissions_unique'
            );
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->uuid('team_id')->nullable()->change();
        });

        Schema::table('permissions', function (Blueprint $table) {
            if (!Schema::hasColumn('permissions', 'team_id')) {
                $table->uuid('team_id')->nullable()->index();
            } else {
                $table->uuid('team_id')->nullable()->change();
            }
        });
    }
};
