<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Service;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Expand enum to include 'main'
        try {
            DB::statement("ALTER TABLE `services` MODIFY COLUMN `type` ENUM('main', 'subdomain', 'subfolder') NOT NULL DEFAULT 'subdomain'");
        } catch (\Throwable $e) {}

        // 2. Add main vardicrm service if not exists
        if (!Service::where('domain', 'vardicrm.ir')->exists() && !Service::where('name', 'vardicrm')->exists()) {
            Service::create([
                'name' => 'vardicrm',
                'domain' => 'vardicrm.ir',
                'type' => 'main',
                'path' => '/var/www/server-panel',
            ]);
        }
    }

    public function down(): void
    {
        Service::where('domain', 'vardicrm.ir')->where('type', 'main')->delete();
    }
};
