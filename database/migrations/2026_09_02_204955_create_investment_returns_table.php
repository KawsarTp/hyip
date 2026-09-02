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
        Schema::create('investment_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investment_id')->constrained()->cascadeOnDelete();
            $table->string('period_key', 100);
            $table->dateTime('period_start');
            $table->dateTime('period_end');
            $table->unsignedBigInteger('opening_principal_minor');
            $table->unsignedInteger('eligible_days');
            $table->unsignedInteger('rate_bps');
            $table->unsignedBigInteger('return_amount_minor');
            $table->string('status')->default('accrued');
            $table->string('formula_version', 40);
            $table->json('calculation_snapshot');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('ledger_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->unique(['investment_id', 'period_key']);
            $table->index(['status', 'period_end']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investment_returns');
    }
};
