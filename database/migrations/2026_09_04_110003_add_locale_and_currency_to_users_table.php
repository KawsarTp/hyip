<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('locale', 10)->nullable()->after('status');
            $table->string('currency_code', 3)->nullable()->after('locale');
            $table->index(['locale', 'currency_code'], 'users_locale_currency_idx');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('users_locale_currency_idx');
            $table->dropColumn(['locale', 'currency_code']);
        });
    }
};
