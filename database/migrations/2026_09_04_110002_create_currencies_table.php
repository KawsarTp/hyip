<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 3)->unique();
            $table->string('name', 80);
            $table->string('symbol', 10);
            $table->unsignedTinyInteger('decimal_places')->default(2);
            $table->decimal('rate_to_default', 20, 10)->default(1);
            $table->string('rate_source', 80)->nullable();
            $table->timestamp('rate_updated_at')->nullable();
            $table->string('symbol_position', 6)->default('prefix');
            $table->string('thousands_separator', 2)->default(',');
            $table->string('decimal_separator', 2)->default('.');
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_enabled', 'sort_order'], 'currencies_enabled_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
