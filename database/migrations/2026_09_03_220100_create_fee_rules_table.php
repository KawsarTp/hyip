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
        Schema::create('fee_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('event', 30);
            $table->string('currency', 3)->nullable();
            $table->unsignedBigInteger('fee_fixed_minor')->default(0);
            $table->unsignedInteger('fee_percent_bps')->default(0);
            $table->unsignedBigInteger('maximum_fee_minor')->nullable();
            $table->unsignedBigInteger('tax_fixed_minor')->default(0);
            $table->unsignedInteger('tax_percent_bps')->default(0);
            $table->string('tax_basis', 10)->default('fee');
            $table->unsignedInteger('priority')->default(0);
            $table->boolean('is_enabled')->default(true);
            $table->timestamp('effective_from')->nullable();
            $table->timestamp('effective_until')->nullable();
            $table->timestamps();
            $table->index(['event', 'currency', 'is_enabled']);
            $table->index(['effective_from', 'effective_until']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_rules');
    }
};
