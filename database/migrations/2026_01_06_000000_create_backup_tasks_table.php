<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // path to service directory relative to /var/www/service
            $table->string('service_path');
            // optional MySQL database name associated with this service
            $table->string('db_name')->nullable();
            $table->boolean('files_enabled')->default(true);
            $table->boolean('db_enabled')->default(false);
            // cron expression (e.g. */6 * * * *)
            $table->string('cron_expression')->nullable();
            // remote FTP settings
            $table->boolean('remote_enabled')->default(false);
            $table->string('remote_host')->nullable();
            $table->string('remote_user')->nullable();
            $table->string('remote_password')->nullable();
            $table->string('remote_path')->nullable();
            // how many days to keep backups locally
            $table->unsignedInteger('local_retention_days')->default(7);
            // how many days to keep backups on remote server
            $table->unsignedInteger('remote_retention_days')->default(30);
            $table->timestamp('last_run_at')->nullable();
            $table->string('last_status')->nullable();
            $table->string('last_log_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_tasks');
    }
};