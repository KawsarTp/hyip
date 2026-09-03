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
        Schema::table('fee_rules', function (Blueprint $table) {
            $table->string('country', 2)->nullable()->after('currency');
            $table->index(['event', 'country', 'currency', 'is_enabled']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fee_rules', function (Blueprint $table) {
            $table->dropIndex(['event', 'country', 'currency', 'is_enabled']);
            $table->dropColumn('country');
        });
    }
};
