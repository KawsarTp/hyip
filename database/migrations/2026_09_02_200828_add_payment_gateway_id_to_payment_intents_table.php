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
        Schema::table('payment_intents', function (Blueprint $table) {
            $table->foreignId('payment_gateway_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->index(['payment_gateway_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_intents', function (Blueprint $table) {
            $table->dropForeign(['payment_gateway_id']);
            $table->dropIndex(['payment_gateway_id', 'status']);
            $table->dropColumn('payment_gateway_id');
        });
    }
};
