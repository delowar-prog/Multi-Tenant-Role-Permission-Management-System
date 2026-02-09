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
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();

            $table->uuid('tenant_id')->nullable();
            $table->uuid('branch_id')->nullable();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('ticket_no')->unique();
            $table->foreignId('category_id')->nullable()
                ->constrained('support_categories')
                ->nullOnDelete();

            $table->string('subject');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['open', 'pending', 'resolved', 'closed'])->default('open');

            $table->foreignId('assigned_to')->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('last_reply_at')->nullable();

            $table->timestamps();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');
            $table->foreign('branch_id')
                ->references('id')
                ->on('branches')
                ->onDelete('cascade');

            $table->index(['tenant_id', 'branch_id']);
            $table->index(['status', 'priority']);
            $table->index('last_reply_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
