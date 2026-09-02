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
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ledger_account_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('currency', 3);
            $table->string('type');
            $table->unsignedBigInteger('balance_minor')->default(0);
            $table->unique(['user_id', 'currency', 'type']);
            $table->index(['user_id', 'currency']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
