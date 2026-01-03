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
            $table->uuid('team_id')->change();
        });

        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->uuid('team_id')->change();
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->uuid('team_id')->nullable()->change();
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->uuid('team_id')->nullable();
        });
    }
};
