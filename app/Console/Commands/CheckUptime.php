<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckUptime extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uptime:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check uptime of all services';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $services = \App\Models\Service::all();
        $this->info("Checking uptime for " . $services->count() . " services...");

        foreach ($services as $service) {
            $domain = $service->type === 'subfolder' 
                ? env('APP_MAIN_DOMAIN') . '/' . $service->domain 
                : $service->domain;
                
            $url = 'http://' . $domain;
            
            try {
                $response = \Illuminate\Support\Facades\Http::timeout(10)->get($url);
                $isOnline = $response->successful() || $response->redirect();
            } catch (\Exception $e) {
                $isOnline = false;
            }

            $service->update([
                'is_online' => $isOnline,
                'last_checked_at' => now(),
            ]);

            $this->info("{$url} is " . ($isOnline ? 'Online' : 'Offline'));
        }

        $this->info("Uptime check completed.");
    }
}
