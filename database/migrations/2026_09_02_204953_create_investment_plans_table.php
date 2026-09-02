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
        Schema::create('investment_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->text('description');
            $table->string('currency', 3)->default('USD');
            $table->string('risk_rating', 30);
            $table->unsignedBigInteger('minimum_amount_minor');
            $table->unsignedBigInteger('maximum_amount_minor')->nullable();
            $table->unsignedBigInteger('capacity_minor')->nullable();
            $table->unsignedBigInteger('current_invested_minor')->default(0);
            $table->unsignedInteger('term_days');
            $table->unsignedInteger('lock_days')->default(0);
            $table->unsignedInteger('projected_rate_bps');
            $table->string('rate_type', 30)->default('annual_simple');
            $table->string('calculation_frequency', 30)->default('daily');
            $table->unsignedSmallInteger('day_count_basis')->default(365);
            $table->boolean('compounding')->default(false);
            $table->string('status')->default('draft');
            $table->unsignedInteger('version')->default(1);
            $table->timestamp('effective_at')->nullable();
            $table->index(['status', 'currency', 'sort_order']);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investment_plans');
    }
};
