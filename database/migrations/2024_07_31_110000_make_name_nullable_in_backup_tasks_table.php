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
        // This migration makes the 'name' column nullable to prevent errors
        // if the column hasn't been dropped yet.
        if (Schema::hasColumn('backup_tasks', 'name')) {
            Schema::table('backup_tasks', function (Blueprint $table) {
                $table->string('name')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('backup_tasks', 'name')) {
            Schema::table('backup_tasks', function (Blueprint $table) {
                // Reverting this is tricky as we don't know the original state.
                // We'll assume it was not nullable.
                // This is unlikely to be used in production anyway.
                $table->string('name')->nullable(false)->change();
            });
        }
    }
};
