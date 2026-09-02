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
        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_intent_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('reference')->unique();
            $table->string('idempotency_key')->nullable()->unique();
            $table->string('method');
            $table->string('currency', 3);
            $table->unsignedBigInteger('amount_minor');
            $table->string('status')->default('pending');
            $table->string('external_reference')->nullable()->index();
            $table->timestamp('settled_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->index(['user_id', 'status']);
            $table->index(['currency', 'status']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deposits');
    }
};
