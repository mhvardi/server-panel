<?php

namespace Tests\Feature;

use App\Mail\DailyBackupReport;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BackupDailyReportTest extends TestCase
{
    use RefreshDatabase;
    public function test_daily_backup_report_sends_email_with_correct_data(): void
    {
        Mail::fake();

        // Create a test service
        $service = Service::create([
            'name' => 'test-service',
            'domain' => 'test.local',
            'path' => storage_path('app/test-service'),
            'type' => 'subdomain',
        ]);

        // Mock backup settings file
        $backupDir = $service->path . '/.backup';
        File::ensureDirectoryExists($backupDir);
        File::put($backupDir . '/settings.json', json_encode([
            'last_backup' => '2026-09-09 12:00:00',
            'last_backup_status' => 'موفق',
            'last_backup_size_mb' => 25.5,
            'last_ftp_uploaded' => true,
        ]));

        $exitCode = \Illuminate\Support\Facades\Artisan::call('backup:send-daily-report', ['--email' => 'admin@example.com']);
        $this->assertEquals(0, $exitCode);

        Mail::assertSent(DailyBackupReport::class, function ($mail) {
            $data = $mail->reportData;
            $this->assertNotEmpty($data);
            $found = collect($data)->firstWhere('name', 'test-service');
            $this->assertNotNull($found);
            $this->assertEquals('موفق', $found['status']);
            $this->assertEquals('بله', $found['ftp']);
            $this->assertEquals(25.5, $found['size']);
            return true;
        });

        // Cleanup
        File::deleteDirectory($service->path);
    }
}
