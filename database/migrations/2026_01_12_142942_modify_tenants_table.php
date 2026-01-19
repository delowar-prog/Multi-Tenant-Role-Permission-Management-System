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
        Schema::table('tenants', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->constrained();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->date('subscription_started_at')->nullable();
            $table->date('subscription_expires_at')->nullable();
            $table->enum('subscription_status', ['active', 'expired', 'cancelled']);
            $table->date('trial_ends_at')->nullable();
            $table->string('logo')->nullable();
            $table->string('primary_color')->default('#2563eb');
            $table->string('secondary_color')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
