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
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_gateway_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('bank_name');
            $table->string('account_name');
            $table->text('account_number')->nullable();
            $table->text('iban')->nullable();
            $table->text('swift_code')->nullable();
            $table->string('branch')->nullable();
            $table->text('instructions')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->boolean('is_enabled')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->index(['payment_gateway_id', 'is_enabled']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
