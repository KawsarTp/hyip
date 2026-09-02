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
        Schema::create('investments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('investment_plan_id')->constrained()->restrictOnDelete();
            $table->uuid('reference')->unique();
            $table->unsignedInteger('plan_version');
            $table->string('currency', 3);
            $table->unsignedBigInteger('principal_minor');
            $table->unsignedBigInteger('projected_return_minor')->default(0);
            $table->unsignedBigInteger('accrued_return_minor')->default(0);
            $table->unsignedBigInteger('released_return_minor')->default(0);
            $table->string('status')->default('active');
            $table->timestamp('start_at');
            $table->timestamp('maturity_at');
            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamp('matured_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('risk_acknowledged_at');
            $table->json('terms_snapshot');
            $table->json('metadata')->nullable();
            $table->index(['user_id', 'status']);
            $table->index(['status', 'maturity_at']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investments');
    }
};
