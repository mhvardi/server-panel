<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Morilog\Jalali\Jalalian;

class InstallController extends Controller
{
    public function showInstallForm()
    {
        if (file_exists(storage_path('installed'))) {
            return redirect('/');
        }
        return view('install');
    }

    public function install(Request $request)
    {
        if (file_exists(storage_path('installed'))) {
            return redirect('/');
        }

        $request->validate([
            'db_host' => 'required',
            'db_port' => 'required',
            'db_database' => 'required',
            'db_username' => 'required',
            // 'db_password' => 'required', // Password can be empty
            'admin_name' => 'required',
            'admin_email' => 'required|email',
            'admin_password' => 'required|min:8|confirmed',
        ]);

        // 1. Update .env file
        $this->updateEnv([
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $request->db_host,
            'DB_PORT' => $request->db_port,
            'DB_DATABASE' => $request->db_database,
            'DB_USERNAME' => $request->db_username,
            'DB_PASSWORD' => $request->db_password,
        ]);

        // 1.5 Ensure APP_KEY exists (بدون اجرای artisan برای جلوگیری از خطاهای اتصال DB در سرویس‌پراوایدرها)
        $this->ensureAppKeyInEnv();

        // 2. Run Migrations
        // We need to set the config at runtime to use the new credentials immediately
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.host' => $request->db_host,
            'database.connections.mysql.port' => $request->db_port,
            'database.connections.mysql.database' => $request->db_database,
            'database.connections.mysql.username' => $request->db_username,
            'database.connections.mysql.password' => $request->db_password,
        ]);

        // Reconnect to the database
        try {
            DB::purge('mysql');
            DB::reconnect('mysql');
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            return back()->withErrors(['db_error' => 'Could not connect to the database. Please check your credentials. ' . $e->getMessage()])->withInput();
        }

        try {
            Artisan::call('migrate', ['--force' => true]);
        } catch (\Exception $e) {
            return back()->withErrors(['migration_error' => 'خطا در اجرای مایگریشن‌ها: ' . $e->getMessage()])->withInput();
        }

        // 3. Create Admin User
        try {
            User::create([
                'name' => $request->admin_name,
                'email' => $request->admin_email,
                'password' => Hash::make($request->admin_password),
            ]);
        } catch (\Exception $e) {
            return back()->withErrors(['user_error' => 'Could not create admin user: ' . $e->getMessage()])->withInput();
        }

        // 4. Mark as installed
        // Use Jalali date for installation timestamp if package is available, otherwise fallback to Gregorian
        $date = class_exists(Jalalian::class) ? Jalalian::now()->format('Y/m/d H:i:s') : date('Y-m-d H:i:s');
        file_put_contents(storage_path('installed'), 'installed on ' . $date);

        return redirect('/')->with('success', 'Installation completed successfully!');
    }

    protected function updateEnv(array $data = []): void
    {
        $envPath = base_path('.env');
        $envExamplePath = base_path('.env.example');

        // اگر .env وجود ندارد، آن را از روی .env.example بساز (با حفظ کامنت‌ها و ترتیب فایل نمونه)
        if (!file_exists($envPath)) {
            if (file_exists($envExamplePath)) {
                copy($envExamplePath, $envPath);
            } else {
                file_put_contents($envPath, "");
            }
        }

        $envContent = file_get_contents($envPath) ?: "";

        foreach ($data as $key => $value) {
            $cleanValue = $value;
            if (is_string($cleanValue)) {
                $cleanValue = trim($cleanValue);
                // Remove surrounding quotes if they exist
                if ((str_starts_with($cleanValue, '"') && str_ends_with($cleanValue, '"')) ||
                    (str_starts_with($cleanValue, "'") && str_ends_with($cleanValue, "'"))
                ) {
                    $cleanValue = substr($cleanValue, 1, -1);
                }
            }

            // 1) اگر کلید فعال وجود دارد، همان را جایگزین کن
            if (preg_match("/^{$key}\s*=.*$/m", $envContent)) {
                $envContent = preg_replace("/^{$key}\s*=.*$/m", "{$key}={$cleanValue}", $envContent, 1);
                continue;
            }

            // 2) اگر فقط نسخه کامنت‌شده وجود دارد، خط فعال را بعد از آن اضافه کن (کامنت را دست نزن)
            if (preg_match("/^#\s*{$key}\s*=.*$/m", $envContent)) {
                $envContent = preg_replace("/^#\s*{$key}\s*=.*$/m", "$0\n{$key}={$cleanValue}", $envContent, 1);
                continue;
            }

            // 3) در غیر این صورت به انتهای فایل اضافه کن
            $envContent = rtrim($envContent) . "\n{$key}={$cleanValue}\n";
        }

        file_put_contents($envPath, $envContent);
    }

    protected function ensureAppKeyInEnv(): void
    {
        $envPath = base_path('.env');
        if (!file_exists($envPath)) {
            return;
        }

        $envContent = file_get_contents($envPath) ?: '';

        // اگر APP_KEY خالی است یا وجود ندارد، یک کلید جدید تولید و ست کن
        $hasActiveKey = preg_match('/^APP_KEY\s*=\s*(.+)?$/m', $envContent, $m);
        $current = $hasActiveKey ? trim($m[1] ?? '') : '';

        if ($current === '' || $current === '""' || $current === "''") {
            $newKey = 'base64:' . base64_encode(random_bytes(32));
            $this->updateEnv(['APP_KEY' => $newKey]);
        } elseif (!$hasActiveKey) {
            $newKey = 'base64:' . base64_encode(random_bytes(32));
            $this->updateEnv(['APP_KEY' => $newKey]);
        }
    }
}
