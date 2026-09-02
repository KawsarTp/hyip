<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('investment_commission_bps')->default(500);
            $table->unsignedInteger('hold_days')->default(30);
            $table->unsignedBigInteger('minimum_qualifying_investment_minor')->default(10000);
            $table->boolean('is_enabled')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_settings');
    }
};
