<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translation_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('language_id')->constrained()->cascadeOnDelete();
            $table->string('group_name', 80)->default('messages');
            $table->string('key', 191);
            $table->longText('value');
            $table->timestamps();

            $table->unique(['language_id', 'group_name', 'key'], 'translation_lines_language_group_key_unique');
            $table->index(['language_id', 'group_name'], 'translation_lines_language_group_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translation_lines');
    }
};
