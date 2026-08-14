<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * BackupTask represents a scheduled backup for a specific service (subdomain).
 *
 * Each task can define whether file and/or database backup is enabled, the schedule (cron expression),
 * retention policies and remote FTP settings. Backups are executed by a queue job
 * (RunBackupTaskJob). See app/Jobs/RunBackupTaskJob.php for details.
 */
class BackupTask extends Model
{
    protected $fillable = [
        'name',
        'service_path',
        'db_name',
        'files_enabled',
        'db_enabled',
        'cron_expression',
        'remote_enabled',
        'remote_host',
        'remote_user',
        'remote_password',
        'remote_path',
        'local_retention_days',
        'remote_retention_days',
        'last_run_at',
        'last_status',
        'last_log_path',
    ];

    protected $casts = [
        'files_enabled'         => 'boolean',
        'db_enabled'            => 'boolean',
        'remote_enabled'        => 'boolean',
        'local_retention_days'  => 'integer',
        'remote_retention_days' => 'integer',
        'last_run_at'           => 'datetime',
    ];
}