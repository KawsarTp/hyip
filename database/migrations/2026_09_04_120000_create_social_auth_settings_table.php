<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_auth_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 30)->unique();
            $table->string('client_id')->nullable();
            $table->text('client_secret')->nullable();
            $table->string('redirect_url')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_auth_settings');
    }
};
