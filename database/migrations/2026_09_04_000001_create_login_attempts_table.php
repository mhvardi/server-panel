<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('email')->nullable();
            $table->string('ip_address', 45);
            $table->string('country', 5)->nullable();      // ISO code, e.g. "IR"
            $table->string('country_name', 100)->nullable();
            $table->boolean('success')->default(false);
            $table->text('user_agent')->nullable();
            $table->string('blocked_reason')->nullable();  // e.g. "geo_restriction", "rate_limit"
            $table->timestamps();

            $table->index(['ip_address', 'created_at']);
            $table->index(['success', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
    }
};
