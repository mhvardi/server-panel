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
        if (Schema::hasTable('login_attempts') && Schema::hasColumn('login_attempts', 'country')) {
            try {
                Schema::table('login_attempts', function (Blueprint $table) {
                    $table->string('country', 20)->nullable()->change();
                });
            } catch (\Throwable $e) {
                // Ignore if driver or database does not support column modification
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('login_attempts') && Schema::hasColumn('login_attempts', 'country')) {
            try {
                Schema::table('login_attempts', function (Blueprint $table) {
                    $table->string('country', 5)->nullable()->change();
                });
            } catch (\Throwable $e) {
                // Ignore
            }
        }
    }
};
