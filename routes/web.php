<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CronJobController;
use App\Http\Controllers\BackupTaskController;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::resource('services', ServiceController::class);

Route::prefix('cron-jobs')->name('cron_jobs.')->group(function () {
    Route::get('/', [CronJobController::class, 'index'])->name('index');
    Route::post('/', [CronJobController::class, 'store'])->name('store');
    Route::put('/{id}', [CronJobController::class, 'update'])->name('update');
    Route::delete('/{id}', [CronJobController::class, 'destroy'])->name('destroy');
});

Route::prefix('backup-tasks')->name('backup_tasks.')->group(function () {
    Route::get('/', [BackupTaskController::class, 'index'])->name('index');
    Route::get('/{service}/settings', [BackupTaskController::class, 'settings'])->name('settings');
    Route::post('/{service}/settings', [BackupTaskController::class, 'saveSettings'])->name('save_settings');
    Route::post('/{service}/run', [BackupTaskController::class, 'run'])->name('run');
    Route::get('/{service}/log', [BackupTaskController::class, 'getLog'])->name('log');
    Route::post('/test-ftp', [BackupTaskController::class, 'testFtp'])->name('test_ftp');
    Route::post('/{service}/db-now', [BackupTaskController::class, 'backupDatabaseNow'])->name('db_now');
    Route::post('/{service}/files-now', [BackupTaskController::class, 'backupFilesNow'])->name('files_now');
    Route::get('/{service}/download/{filename}', [BackupTaskController::class, 'downloadBackup'])->name('download');
});
