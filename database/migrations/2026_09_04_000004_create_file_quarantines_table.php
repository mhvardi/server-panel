<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_quarantines', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('original_path');
            $table->string('quarantine_path');
            $table->string('reason');
            $table->string('threat_type', 50)->default('suspicious_code'); // webshell, malicious_ext, dangerous_func
            $table->string('file_hash', 64)->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->timestamps();

            $table->index('file_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_quarantines');
    }
};
