<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Services\DatabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class DatabaseController extends Controller
{
    protected $databaseService;

    public function __construct(DatabaseService $databaseService)
    {
        $this->databaseService = $databaseService;
    }

    /**
     * Get base URL for phpMyAdmin
     */
        public static function getPhpMyAdminUrl(?string $dbName = null): string
    {
        $baseUrl = env('PHPMYADMIN_URL', 'https://vardicrm.ir/phpmyadmin');
        $baseUrl = rtrim($baseUrl, '/');
        if ($dbName) {
            if (str_contains($baseUrl, '?')) {
                return "{$baseUrl}&db=" . urlencode($dbName);
            }
            return "{$baseUrl}/index.php?route=/database/structure&db=" . urlencode($dbName);
        }
        return $baseUrl;
    }

    /**
     * Display a listing of databases & service connections
     */
    public function index()
    {
        // 1. Fetch all Services and extract their DB config from .env
        $services = Service::all();
        $serviceDatabases = [];
        $totalConnectedServices = 0;
        $totalDbSize = 0.0;

        foreach ($services as $service) {
            $dbConfig = $service->getDatabaseConfig();
            if (!empty($dbConfig['database'])) {
                $totalConnectedServices++;
                $totalDbSize += (float) ($dbConfig['size_mb'] ?? 0);
            }
            $serviceDatabases[] = [
                'service' => $service,
                'config' => $dbConfig,
                'pma_url' => self::getPhpMyAdminUrl($dbConfig['database'] ?? null),
            ];
        }

        // 2. Fetch server-level databases & users
        $databases = [];
        $users = [];
        $dbServerError = null;

        try {
            $databases = $this->databaseService->listDatabases();
            if (!empty($databases)) {
                $totalDbSize = array_sum(array_column($databases, 'size'));
            }
        } catch (\Throwable $e) {
            Log::warning('Database server listDatabases warning: ' . $e->getMessage());
            $dbServerError = $e->getMessage();
        }

        try {
            $users = $this->databaseService->listUsers();
        } catch (\Throwable $e) {
            Log::warning('Database server listUsers warning: ' . $e->getMessage());
        }

        $stats = [
            'total_services' => $services->count(),
            'connected_services' => $totalConnectedServices,
            'total_databases' => count($databases) > 0 ? count($databases) : $totalConnectedServices,
            'total_users' => count($users),
            'total_size_mb' => $totalDbSize,
        ];

        $phpmyadminBaseUrl = self::getPhpMyAdminUrl();

        return view('databases.index', compact(
            'serviceDatabases',
            'databases',
            'users',
            'stats',
            'dbServerError',
            'phpmyadminBaseUrl'
        ));
    }

    /**
     * Show the form for creating a new database
     */
    public function create()
    {
        return view('databases.create');
    }

    /**
     * Store a newly created database
     */
    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:64|regex:/^[a-zA-Z0-9_]+$/',
            'charset' => 'nullable|string|in:utf8,utf8mb4,latin1',
            'collation' => 'nullable|string',
        ];

        $shouldCreateUser = $request->has('create_user') && ($request->create_user == '1' || $request->create_user == 'true');

        if ($shouldCreateUser) {
            $rules['username'] = 'required|string|max:32|regex:/^[a-zA-Z0-9_]+$/';
            $rules['password'] = 'required|string|min:8';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $charset = $request->charset ?? 'utf8mb4';
            $collation = $request->collation ?? ($charset === 'utf8mb4' ? 'utf8mb4_unicode_ci' : 'utf8_general_ci');

            $this->databaseService->createDatabase($request->name, $charset, $collation);

            if ($shouldCreateUser) {
                $host = $request->input('host', 'localhost');
                $this->databaseService->createUser($request->username, $request->password, $host);
                $this->databaseService->grantPrivileges($request->username, $request->name, $host, 'ALL PRIVILEGES');

                return redirect()->route('databases.index')
                    ->with('success', 'پایگاه‌داده و کاربر با موفقیت ایجاد شده و دسترسی‌ها متصل گردیدند.');
            }

            return redirect()->route('databases.index')
                ->with('success', 'پایگاه‌داده با موفقیت ایجاد شد!');
        } catch (\Exception $e) {
            Log::error('Database creation error: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors(['error' => 'خطا در عملیات سرور: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Display the specified database
     */
    public function show($database)
    {
        try {
            $database = urldecode($database);

            if (!preg_match('/^[a-zA-Z0-9_]+$/', $database)) {
                return redirect()->route('databases.index')
                    ->with('error', 'نام پایگاه‌داده نامعتبر است.');
            }

            $details = $this->databaseService->getDatabaseDetails($database);
            $pmaUrl = self::getPhpMyAdminUrl($database);

            return view('databases.show', compact('details', 'pmaUrl'));
        } catch (\Exception $e) {
            Log::error('Database show error: ' . $e->getMessage(), [
                'database' => $database ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('databases.index')
                ->with('error', 'خطا در بارگذاری اطلاعات پایگاه‌داده: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified database (Disabled by safety policy)
     */
    public function destroy($database)
    {
        return redirect()->route('databases.index')
            ->with('error', 'عملیات حذف پایگاه‌داده از طریق پنل جهت جلوگیری از خطای انسانی و از دست رفتن داده‌ها غیرفعال است.');
    }

    /**
     * Create a new database user
     */
    public function createUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:32|regex:/^[a-zA-Z0-9_]+$/',
            'password' => 'required|string|min:8|confirmed',
            'host' => 'nullable|string|in:localhost,%,127.0.0.1',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $host = $request->host ?? 'localhost';
            $this->databaseService->createUser($request->username, $request->password, $host);

            return redirect()->route('databases.index')
                ->with('success', 'کاربر پایگاه‌داده با موفقیت ایجاد شد!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Delete a database user
     */
    public function deleteUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'host' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        try {
            $host = $request->host ?? 'localhost';
            $this->databaseService->deleteUser($request->username, $host);

            return redirect()->route('databases.index')
                ->with('success', 'کاربر پایگاه‌داده با موفقیت حذف شد!');
        } catch (\Exception $e) {
            return redirect()->route('databases.index')
                ->with('error', 'خطا در حذف کاربر: ' . $e->getMessage());
        }
    }

    /**
     * Grant privileges to a user on a database
     */
    public function grantPrivileges(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'database' => 'required|string',
            'host' => 'nullable|string',
            'privileges' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        try {
            $host = $request->host ?? 'localhost';
            $privileges = $request->privileges ?? 'ALL PRIVILEGES';

            $this->databaseService->grantPrivileges(
                $request->username,
                $request->database,
                $host,
                $privileges
            );

            if ($request->has('from_show')) {
                return redirect()->route('databases.show', $request->database)
                    ->with('success', 'دسترسی‌ها با موفقیت اعطا شد!');
            }

            return redirect()->route('databases.index')
                ->with('success', 'دسترسی‌ها با موفقیت به ' . $request->username . ' برای دیتابیس ' . $request->database . ' اعطا شد!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Revoke privileges from a user on a database
     */
    public function revokePrivileges(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'database' => 'required|string',
            'host' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        try {
            $host = $request->host ?? 'localhost';

            $this->databaseService->revokePrivileges(
                $request->username,
                $request->database,
                $host
            );

            return redirect()->route('databases.show', $request->database)
                ->with('success', 'دسترسی‌ها با موفقیت لغو شد!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Change user password
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
            'host' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $host = $request->host ?? 'localhost';
            $this->databaseService->changeUserPassword(
                $request->username,
                $request->password,
                $host
            );

            return redirect()->route('databases.index')
                ->with('success', 'رمز عبور کاربر ' . $request->username . ' با موفقیت به‌روزرسانی شد!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Test connection for a service or custom credentials (AJAX)
     */
    public function testConnection(Request $request)
    {
        $serviceId = $request->input('service_id');
        if ($serviceId) {
            $service = Service::find($serviceId);
            if (!$service) {
                return response()->json(['success' => false, 'message' => 'سرویس مورد نظر یافت نشد.']);
            }
            $config = $service->getDatabaseConfig();
        } else {
            $config = [
                'host' => $request->input('host', '127.0.0.1'),
                'port' => $request->input('port', '3306'),
                'database' => $request->input('database'),
                'username' => $request->input('username'),
                'password' => $request->input('password', ''),
            ];
        }

        if (empty($config['database']) || empty($config['username'])) {
            return response()->json([
                'success' => false,
                'message' => 'اطلاعات نام پایگاه‌داده یا نام کاربری در تنظیمات یافت نشد.',
            ]);
        }

        try {
            $host = !empty($config['host']) ? $config['host'] : '127.0.0.1';
            $port = !empty($config['port']) ? $config['port'] : '3306';
            $dsn = "mysql:host={$host};port={$port};dbname={$config['database']};charset=utf8mb4";

            $pdo = new \PDO($dsn, $config['username'], $config['password'], [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_TIMEOUT => 3,
            ]);

            $stmt = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = " . $pdo->quote($config['database']));
            $tableCount = (int) $stmt->fetchColumn();

            return response()->json([
                'success' => true,
                'message' => "ارتباط پایگاه‌داده با موفقیت برقرار است ({$tableCount} جدول)",
                'tables' => $tableCount,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در برقراری ارتباط: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Direct download of SQL dump for a specific service
     */
    public function downloadServiceSqlBackup(Service $service)
    {
        $config = $service->getDatabaseConfig();
        $dbName = $config['database'];
        if (empty($dbName)) {
            return back()->with('error', 'نام پایگاه‌داده برای این سرویس در فایل .env یافت نشد.');
        }

        $timestamp = date('Y-m-d_H-i-s');
        $safeServiceName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $service->name);
        $fileName = 'backup_' . $safeServiceName . '_' . $dbName . '_' . $timestamp . '.sql.gz';
        $tempDir = storage_path('app/temp_db_backups');

        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        $filePath = $tempDir . '/' . $fileName;

        $dbHost = !empty($config['host']) ? $config['host'] : '127.0.0.1';
        $dbPort = !empty($config['port']) ? $config['port'] : '3306';
        $dbUser = !empty($config['username']) ? $config['username'] : (env('MYSQL_ROOT_USERNAME') ?: 'root');
        $dbPass = $config['password'] ?? (env('MYSQL_ROOT_PASSWORD') ?: '');

        $passParam = ($dbPass !== '' && $dbPass !== null) ? '-p' . escapeshellarg($dbPass) : '';
        $cmd = "mysqldump -h " . escapeshellarg($dbHost) . " -P " . escapeshellarg((string)$dbPort) . " -u " . escapeshellarg($dbUser) . " {$passParam} " . escapeshellarg($dbName) . " | gzip > " . escapeshellarg($filePath);

        $process = Process::run($cmd);
        if (!$process->successful() || !file_exists($filePath) || filesize($filePath) === 0) {
            // Try fallback with root credentials
            $rootUser = env('MYSQL_ROOT_USERNAME', 'root');
            $rootPass = env('MYSQL_ROOT_PASSWORD', '');
            $rootPassParam = ($rootPass !== '' && $rootPass !== null) ? '-p' . escapeshellarg($rootPass) : '';
            $cmdFallback = "mysqldump -h " . escapeshellarg($dbHost) . " -P " . escapeshellarg((string)$dbPort) . " -u " . escapeshellarg($rootUser) . " {$rootPassParam} " . escapeshellarg($dbName) . " | gzip > " . escapeshellarg($filePath);
            $processFallback = Process::run($cmdFallback);

            if (!$processFallback->successful() || !file_exists($filePath) || filesize($filePath) === 0) {
                return back()->with('error', 'خطا در خروجی گرفتن از پایگاه‌داده: ' . ($process->errorOutput() ?: 'امکان ایجاد فایل بکاپ وجود نداشت'));
            }
        }

        return response()->download($filePath, $fileName)->deleteFileAfterSend(true);
    }
}