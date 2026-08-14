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
        Schema::table('backup_tasks', function (Blueprint $table) {
            // Check if columns don't exist before adding/dropping
            if (!Schema::hasColumn('backup_tasks', 'service_id')) {
                $table->foreignId('service_id')->after('id')->constrained()->onDelete('cascade');
            }
            if (Schema::hasColumn('backup_tasks', 'name')) {
                $table->dropColumn('name');
            }
            if (Schema::hasColumn('backup_tasks', 'service_path')) {
                $table->dropColumn('service_path');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('backup_tasks', function (Blueprint $table) {
            if (Schema::hasColumn('backup_tasks', 'service_id')) {
                $table->dropForeign(['service_id']);
                $table->dropColumn('service_id');
            }
            if (!Schema::hasColumn('backup_tasks', 'name')) {
                $table->string('name')->nullable()->after('id');
            }
            if (!Schema::hasColumn('backup_tasks', 'service_path')) {
                $table->string('service_path')->nullable()->after('name');
            }
        });
    }
};
