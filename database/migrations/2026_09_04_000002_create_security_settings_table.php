<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Default security settings
        $defaults = [
            ['key' => 'iran_ip_restriction',    'value' => 'false'],
            ['key' => 'max_login_attempts',     'value' => '3'],
            ['key' => 'lockout_minutes',        'value' => '1440'], // 24 hours
            ['key' => 'whitelisted_ips',        'value' => "94.183.100.3\n127.0.0.1"],
            ['key' => 'log_login_attempts',     'value' => 'true'],
            ['key' => 'upload_file_scan',       'value' => 'true'],
            ['key' => 'quarantine_infected',    'value' => 'true'],
            ['key' => 'server_monitor_enabled', 'value' => 'true'],
            ['key' => 'last_scan_at',           'value' => ''],
        ];

        foreach ($defaults as $setting) {
            DB::table('security_settings')->insert(array_merge($setting, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('security_settings');
    }
};
