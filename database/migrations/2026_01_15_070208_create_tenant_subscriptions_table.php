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
        Schema::create('tenant_subscriptions', function (Blueprint $table) {
            $table->id();

            $table->uuid('tenant_id')->index();
            $table->unsignedBigInteger('plan_id')->index();

            $table->dateTime('subscription_started_at');
            $table->dateTime('subscription_expires_at')->nullable();

            $table->decimal('amount', 10, 2)->default(0);
            $table->enum('billing_cycle', ['monthly', 'yearly', 'lifetime']);

            $table->enum('status', [
                'active',
                'expired',
                'upgraded',
                'cancelled'
            ])->default('active');

            $table->timestamps();

            // 🔒 Optional Foreign Keys
            // $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            // $table->foreign('plan_id')->references('id')->on('plans')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_subscriptions');
    }
};
