<?php

namespace App\Http\Controllers;

use App\Services\DatabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class DatabaseController extends Controller
{
    protected $databaseService;

    public function __construct(DatabaseService $databaseService)
    {
        $this->databaseService = $databaseService;
    }

    /**
     * Display a listing of databases
     */
    public function index()
    {
        try {
            $databases = $this->databaseService->listDatabases();
            $users = $this->databaseService->listUsers();

            return view('databases.index', compact('databases', 'users'));
        } catch (\Exception $e) {
            Log::error('Database index error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            try {
                $users = $this->databaseService->listUsers();
            } catch (\Exception $userError) {
                $users = [];
            }

            $databases = [];
            $error = 'Failed to load databases: ' . $e->getMessage();

            return view('databases.index', compact('databases', 'users', 'error'));
        }
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
        // قوانین پایه برای دیتابیس
        $rules = [
            'name' => 'required|string|max:64|regex:/^[a-zA-Z0-9_]+$/',
            'charset' => 'nullable|string|in:utf8,utf8mb4,latin1',
            'collation' => 'nullable|string',
        ];

        // بررسی اینکه آیا درخواست از فرم ایجاد سریع پایگاه‌داده + کاربر ارسال شده است یا خیر
        $shouldCreateUser = $request->has('create_user') && ($request->create_user == '1' || $request->create_user == 'true');

        if ($shouldCreateUser) {
            $rules['username'] = 'required|string|max:32|regex:/^[a-zA-Z0-9_]+$/';
            $rules['password'] = 'required|string|min:8'; // تاییدیه پسورد برداشته شد تا با فرانت ست باشد
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $charset = $request->charset ?? 'utf8mb4';
            $collation = $request->collation ?? ($charset === 'utf8mb4' ? 'utf8mb4_unicode_ci' : 'utf8_general_ci');

            // گام اول: ساخت دیتابیس
            $this->databaseService->createDatabase($request->name, $charset, $collation);

            // گام دوم: ساخت کاربر و اتصال (در صورت فعال بودن فلگ)
            if ($shouldCreateUser) {
                $host = 'localhost'; // هاست پیش‌فرض پنل

                // ایجاد کاربر در سرویس دیتابیس
                $this->databaseService->createUser($request->username, $request->password, $host);

                // دادن دسترسی‌های کامل دیتابیس جدید به کاربر جدید
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
            return view('databases.show', compact('details'));
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
     * Remove the specified database
     */
    public function destroy($database)
    {
        try {
            $this->databaseService->deleteDatabase($database);

            return redirect()->route('databases.index')
                ->with('success', 'پایگاه‌داده با موفقیت حذف شد!');
        } catch (\Exception $e) {
            return redirect()->route('databases.index')
                ->with('error', 'خطا در حذف پایگاه‌داده: ' . $e->getMessage());
        }
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
                ->with('success', 'رمز عبور با موفقیت تغییر کرد!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }
}