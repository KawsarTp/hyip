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
        Schema::create('withdrawal_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('method', 30);
            $table->string('currency', 3)->default('USD');
            $table->text('instructions');
            $table->string('destination_label', 100)->default('Destination details');
            $table->unsignedBigInteger('minimum_amount_minor')->default(1000);
            $table->unsignedBigInteger('maximum_amount_minor')->nullable();
            $table->unsignedBigInteger('fee_fixed_minor')->default(0);
            $table->unsignedSmallInteger('fee_percent_bps')->default(0);
            $table->boolean('is_enabled')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->index(['currency', 'is_enabled', 'sort_order']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawal_methods');
    }
};
