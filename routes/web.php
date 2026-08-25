<?php

use App\Http\Controllers\LogsController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\SetupAdminController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\DatabaseController;
use App\Http\Middleware\TwoFactorMiddleware;
use App\Http\Controllers\BackupTaskController;
use App\Http\Controllers\CronJobController;
use App\Http\Controllers\DomainMappingController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\FileManagerController;
use App\Http\Controllers\DiskCleanupController;


// Installation Routes
Route::get('/install', [InstallController::class, 'showInstallForm'])->name('install.form');
Route::post('/install', [InstallController::class, 'install'])->name('install.post');

// Setup Admin Routes
Route::get('/setup-admin', [SetupAdminController::class, 'showForm'])->name('setup-admin.form');
Route::post('/setup-admin', [SetupAdminController::class, 'store'])->name('setup-admin.store');

// Public Routes
Route::get('/', function () {
    if (!file_exists(storage_path('installed'))) {
        return redirect()->route('install.form');
    }
    return view('welcome');
})->name('home');

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// 2FA Verification Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/2fa/verify', [TwoFactorController::class, 'showVerifyForm'])->name('2fa.verify');
    Route::post('/2fa/verify', [TwoFactorController::class, 'verify'])->name('2fa.verify.post');
});

// Protected Routes
Route::middleware(['auth', TwoFactorMiddleware::class])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Domain Mapping Routes
    Route::get('/domain-mappings', [DomainMappingController::class, 'index'])->name('domain-mappings.index');
    Route::post('/domain-mappings', [DomainMappingController::class, 'store'])->name('domain-mappings.store');
    Route::post('/domain-mappings/parked', [DomainMappingController::class, 'storeParked'])->name('domain-mappings.store-parked');
    Route::post('/domain-mappings/direct', [DomainMappingController::class, 'storeDirect'])->name('domain-mappings.store-direct');
    Route::delete('/domain-mappings/{domainMapping}', [DomainMappingController::class, 'destroy'])->name('domain-mappings.destroy');
    Route::post('/domain-mappings/{domainMapping}/reprovision', [DomainMappingController::class, 'reprovision'])->name('domain-mappings.reprovision');

    // Backup Task Routes
    Route::get('/backup-tasks', [BackupTaskController::class, 'index'])->name('backup_tasks.index');
    Route::get('/backup-tasks/{service}/settings', [BackupTaskController::class, 'settings'])->name('backup_tasks.settings');
    Route::post('/backup-tasks/{service}/settings', [BackupTaskController::class, 'saveSettings'])->name('backup_tasks.save_settings');
    Route::post('/backup-tasks/{service}/run', [BackupTaskController::class, 'run'])->name('backup_tasks.run');
    Route::get('/backup-tasks/{service}/log', [BackupTaskController::class, 'getLog'])->name('backup_tasks.log');
    Route::get('/backup-tasks/queue-status', [BackupTaskController::class, 'queueStatus'])->name('backup_tasks.queue_status');
    Route::post('/backup-tasks/test-ftp', [BackupTaskController::class, 'testFtp'])->name('backup_tasks.test_ftp');
    Route::post('/backup-tasks/{service}/manual', [BackupTaskController::class, 'manualBackup'])->name('backup_tasks.manual');
    Route::post('/backup-tasks/{service}/db-now', [BackupTaskController::class, 'backupDatabaseNow'])->name('backup_tasks.db_now');
    Route::post('/backup-tasks/{service}/files-now', [BackupTaskController::class, 'backupFilesNow'])->name('backup_tasks.files_now');
    Route::get('/backup-tasks/{service}/download/{filename}', [BackupTaskController::class, 'downloadBackup'])->name('backup_tasks.download');
});

Route::middleware(['auth', TwoFactorMiddleware::class])->group(function () {
    // User Management Routes
    Route::resource('users', UserController::class);

    // Service Management Routes
    Route::resource('services', ServiceController::class);
    Route::get('/services/{service}/analyze', [ServiceController::class, 'analyze'])->name('services.analyze');
    Route::post('/services/{service}/ssl', [ServiceController::class, 'generateSsl'])->name('services.ssl');
    Route::post('/services/{service}/ssl/revoke', [ServiceController::class, 'revokeSsl'])->name('services.ssl.revoke');
    Route::post('/services/{service}/ssl/auto-renew', [ServiceController::class, 'triggerAutoRenew'])->name('services.ssl.auto-renew');
    Route::post('/services/{service}/custom-domain', [ServiceController::class, 'storeCustomDomain'])->name('services.custom-domain.store');
    Route::delete('/services/{service}/custom-domain/{domainMapping}', [ServiceController::class, 'destroyCustomDomain'])->name('services.custom-domain.destroy');
    Route::get('/services/{service}/logs', [ServiceController::class, 'getLogs'])->name('services.logs');

    // Service Extra Actions
    Route::post('/services/{service}/upload', [ServiceController::class, 'uploadFile'])->name('services.upload');
    Route::post('/services/{service}/manual-update', [ServiceController::class, 'manualUpdate'])->name('services.manual-update');
    Route::post('/services/{service}/git', [ServiceController::class, 'gitAction'])->name('services.git');
    Route::post('/services/{service}/command', [ServiceController::class, 'executeCommand'])->name('services.command');
    Route::get('/services/{service}/npm-status', [ServiceController::class, 'npmInstallStatus'])->name('services.npm-status');
    Route::post('/services/{service}/reset', [ServiceController::class, 'reset'])->name('services.reset');

    // File Manager Routes
    Route::get('/services/{service}/file', [ServiceController::class, 'getFile'])->name('services.files.get');
    Route::post('/services/{service}/file', [ServiceController::class, 'saveFile'])->name('services.files.save');
    Route::post('/services/{service}/file/create', [ServiceController::class, 'createFile'])->name('services.files.create');
    Route::post('/services/{service}/file/upload', [ServiceController::class, 'uploadSingleFile'])->name('services.files.upload');

    // 2FA Setup Routes
    Route::get('/2fa/setup', [TwoFactorController::class, 'showSetupForm'])->name('2fa.setup');
    Route::post('/2fa/enable', [TwoFactorController::class, 'enable'])->name('2fa.enable');
    Route::post('/2fa/disable', [TwoFactorController::class, 'disable'])->name('2fa.disable');

    // Database Management Routes
    Route::resource('databases', DatabaseController::class);
    Route::post('/databases/test-connection', [DatabaseController::class, 'testConnection'])->name('databases.test-connection');
    Route::get('/databases/service/{service}/backup', [DatabaseController::class, 'downloadServiceSqlBackup'])->name('databases.service-backup');
    Route::post('/databases/user/create', [DatabaseController::class, 'createUser'])->name('databases.user.create');
    Route::post('/databases/user/delete', [DatabaseController::class, 'deleteUser'])->name('databases.user.delete');
    Route::post('/databases/user/password', [DatabaseController::class, 'changePassword'])->name('databases.user.password');
    Route::post('/databases/user/password-ajax', [DatabaseController::class, 'changePasswordAjax'])->name('databases.user.password.ajax');

    Route::post('/databases/privileges/grant', [DatabaseController::class, 'grantPrivileges'])->name('databases.privileges.grant');
    Route::post('/databases/privileges/revoke', [DatabaseController::class, 'revokePrivileges'])->name('databases.privileges.revoke');

    // Cronjob
    Route::get('/cronjobs', [CronJobController::class, 'index'])->name('cronjobs.index');
    Route::get('/cronjobs/create', [CronJobController::class, 'create'])->name('cronjobs.create');
    Route::post('/cronjobs', [CronJobController::class, 'store'])->name('cronjobs.store');
    Route::get('/cronjobs/{id}/edit', [CronJobController::class, 'edit'])->name('cronjobs.edit');
    Route::put('/cronjobs/{id}', [CronJobController::class, 'update'])->name('cronjobs.update');
    Route::post('/cronjobs/{id}/toggle', [CronJobController::class, 'toggle'])->name('cronjobs.toggle');
    Route::delete('/cronjobs/{id}', [CronJobController::class, 'destroy'])->name('cronjobs.destroy');
    Route::post('/cronjobs/{id}/run', [CronJobController::class, 'run'])->name('cronjobs.run');

    //cach
    Route::post('/admin/clear-cache', [DashboardController::class, 'clearCache'])->name('admin.clear-cache');
});

// LOG
Route::prefix('admin')->middleware(['auth'])->group(function () {
    Route::get('/logs', [LogsController::class, 'index'])->name('admin.logs.index');
    Route::get('/logs/{key}', [LogsController::class, 'view'])->name('admin.logs.view')->where('key', '.*');
    Route::post('/logs/{key}/clear', [LogsController::class, 'clear'])->name('admin.logs.clear')->where('key', '.*');
    Route::get('/logs/{key}/download', [LogsController::class, 'download'])->name('admin.logs.download')->where('key', '.*');
});

// Settings & Update Routes
Route::middleware(['auth', TwoFactorMiddleware::class])->group(function () {
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings/update', [SettingsController::class, 'updatePanel'])->name('settings.update');
    Route::post('/settings/manual-update', [SettingsController::class, 'manualUpdatePanel'])->name('settings.manual-update');
});

// File Manager Routes
Route::middleware(['auth', TwoFactorMiddleware::class])->prefix('file-manager')->name('file-manager.')->group(function () {
    Route::get('/',            [FileManagerController::class, 'index'])->name('index');
    Route::get('/browse',      [FileManagerController::class, 'browse'])->name('browse');
    Route::get('/read',        [FileManagerController::class, 'read'])->name('read');
    Route::post('/save',       [FileManagerController::class, 'save'])->name('save');
    Route::post('/delete',     [FileManagerController::class, 'delete'])->name('delete');
    Route::post('/upload',     [FileManagerController::class, 'upload'])->name('upload');
    Route::get('/download',    [FileManagerController::class, 'download'])->name('download');
    Route::post('/mkdir',      [FileManagerController::class, 'mkdir'])->name('mkdir');
    Route::post('/touch',      [FileManagerController::class, 'touch'])->name('touch');
    Route::post('/rename',     [FileManagerController::class, 'rename'])->name('rename');
    Route::get('/disk-usage',  [FileManagerController::class, 'diskUsage'])->name('disk-usage');
});

// Disk Cleanup Routes
Route::middleware(['auth', TwoFactorMiddleware::class])->prefix('disk-cleanup')->name('disk-cleanup.')->group(function () {
    Route::get('/',           [DiskCleanupController::class, 'index'])->name('index');
    Route::get('/status',     [DiskCleanupController::class, 'status'])->name('status');
    Route::post('/cleanup',   [DiskCleanupController::class, 'cleanup'])->name('cleanup');
    Route::get('/analyze',    [DiskCleanupController::class, 'analyze'])->name('analyze');
    Route::post('/delete',    [DiskCleanupController::class, 'deleteItem'])->name('delete');
});
