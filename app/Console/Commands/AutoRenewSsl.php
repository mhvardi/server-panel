<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;

class AutoRenewSsl extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ssl:auto-renew';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically check and renew expiring SSL certificates with Certbot';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Checking and renewing SSL certificates...");

        if (PHP_OS_FAMILY === 'Windows') {
            $this->warn("Skipping SSL renewal on Windows environment.");
            return 0;
        }

        try {
            $cmd = "sudo certbot renew --no-self-upgrade --post-hook 'systemctl reload nginx'";
            $result = Process::run($cmd);

            if ($result->successful()) {
                $output = $result->output();
                $this->info("Certbot renew output:\n" . $output);
                Log::info("Certbot automatic SSL renewal run successfully.", ['output' => $output]);
                return 0;
            } else {
                $error = $result->errorOutput() ?: $result->output();
                $this->error("Certbot renewal error: " . $error);
                Log::error("Certbot automatic SSL renewal error.", ['error' => $error]);
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("Exception during SSL renewal: " . $e->getMessage());
            Log::error("Exception in AutoRenewSsl command", ['error' => $e->getMessage()]);
            return 1;
        }
    }
}
