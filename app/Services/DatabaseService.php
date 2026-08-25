<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDO;
use PDOException;

class DatabaseService
{
    protected $host;
    protected $port;
    protected $rootUsername;
    protected $rootPassword;

    public function __construct()
    {
        // Get MySQL root credentials from config or env
        // For production, these should be stored securely
        $this->host = config('database.connections.mysql.host', env('DB_HOST', '127.0.0.1'));
        $this->port = config('database.connections.mysql.port', env('DB_PORT', '3306'));

        // First try to get root credentials, if not set, use DB credentials as fallback
        $this->rootUsername = env('MYSQL_ROOT_USERNAME');
        $this->rootPassword = env('MYSQL_ROOT_PASSWORD');

        // If root credentials are not set, use DB credentials (but this might not work for admin operations)
        if (empty($this->rootUsername)) {
            $this->rootUsername = env('DB_USERNAME', 'root');
            $this->rootPassword = env('DB_PASSWORD', '');
        }
    }

    /**
     * Get a root connection to MySQL server (without specifying a database)
     */
    protected function getRootConnection()
    {
        $credentials = [
            // 1. Explicit root credentials from env
            ['user' => env('MYSQL_ROOT_USERNAME'), 'pass' => env('MYSQL_ROOT_PASSWORD')],
            // 2. Config database credentials
            ['user' => config('database.connections.mysql.username'), 'pass' => config('database.connections.mysql.password')],
            // 3. Env DB credentials
            ['user' => env('DB_USERNAME'), 'pass' => env('DB_PASSWORD')],
            // 4. Default root without password
            ['user' => 'root', 'pass' => ''],
            // 5. Default root with root password
            ['user' => 'root', 'pass' => 'root'],
        ];

        $lastError = null;
        $attempted = [];

        foreach ($credentials as $cred) {
            $user = $cred['user'];
            $pass = $cred['pass'] ?? '';
            if (empty($user)) continue;

            $key = $user . '::' . $pass;
            if (isset($attempted[$key])) continue;
            $attempted[$key] = true;

            try {
                $dsn = "mysql:host={$this->host};port={$this->port};charset=utf8mb4";
                $pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 2,
                ]);
                $this->rootUsername = $user;
                $this->rootPassword = $pass;
                return $pdo;
            } catch (PDOException $e) {
                $lastError = $e;
            }
        }

        Log::warning('Database root connection failed for all attempts: ' . ($lastError ? $lastError->getMessage() : 'unknown'));

        throw new \Exception(
            'عدم دسترسی مدیریت به سرور MySQL. ' .
            'برای مدیریت کامل دیتابیس‌های سراسری سرور، متغیرهای MYSQL_ROOT_USERNAME و MYSQL_ROOT_PASSWORD را در فایل .env تنظیم کنید. ' .
            ($lastError ? '(' . $lastError->getMessage() . ')' : '')
        );
    }

    /**
     * List all databases
     */
    public function listDatabases()
    {
        try {
            $pdo = $this->getRootConnection();
            $stmt = $pdo->query("SHOW DATABASES");
            $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Filter out system databases
            $systemDatabases = ['information_schema', 'performance_schema', 'mysql', 'sys'];
            $databases = array_filter($databases, function ($db) use ($systemDatabases) {
                return !in_array($db, $systemDatabases);
            });

            $result = [];
            foreach ($databases as $db) {
                $result[] = [
                    'name' => $db,
                    'size' => $this->getDatabaseSize($db),
                    'tables' => $this->getTableCount($db),
                    'charset' => $this->getDatabaseCharset($db),
                ];
            }

            return array_values($result);
        } catch (\Exception $e) {
            Log::error('Failed to list databases: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a new database
     */
    public function createDatabase($name, $charset = 'utf8mb4', $collation = 'utf8mb4_unicode_ci')
    {
        try {
            // Validate database name
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
                throw new \Exception('Database name can only contain letters, numbers, and underscores');
            }

            $pdo = $this->getRootConnection();

            // Use backticks for database name (identifier), not quotes
            // For charset and collation, we can use quotes as they are string literals
            $dbName = '`' . str_replace('`', '``', $name) . '`';
            $charset = $pdo->quote($charset);
            $collation = $pdo->quote($collation);

            $pdo->exec("CREATE DATABASE {$dbName} CHARACTER SET {$charset} COLLATE {$collation}");

            Log::info("Database created: {$name}");
            return true;
        } catch (PDOException $e) {
            Log::error('Failed to create database: ' . $e->getMessage());
            throw new \Exception('Failed to create database: ' . $e->getMessage());
        }
    }

    /**
     * Delete a database
     */
    public function deleteDatabase($name)
    {
        try {
            // Prevent deletion of system databases
            $systemDatabases = ['information_schema', 'performance_schema', 'mysql', 'sys'];
            if (in_array($name, $systemDatabases)) {
                throw new \Exception('Cannot delete system database');
            }

            $pdo = $this->getRootConnection();
            // Use backticks for database name (identifier)
            $dbName = '`' . str_replace('`', '``', $name) . '`';
            $pdo->exec("DROP DATABASE {$dbName}");

            Log::info("Database deleted: {$name}");
            return true;
        } catch (PDOException $e) {
            Log::error('Failed to delete database: ' . $e->getMessage());
            throw new \Exception('Failed to delete database: ' . $e->getMessage());
        }
    }

    /**
     * Get database size in MB
     */
    protected function getDatabaseSize($database)
    {
        try {
            $pdo = $this->getRootConnection();
            $stmt = $pdo->prepare("
                SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                FROM information_schema.tables
                WHERE table_schema = ?
            ");
            $stmt->execute([$database]);
            $result = $stmt->fetch();
            return $result['size_mb'] ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get table count for a database
     */
    protected function getTableCount($database)
    {
        try {
            $pdo = $this->getRootConnection();
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count
                FROM information_schema.tables
                WHERE table_schema = ?
            ");
            $stmt->execute([$database]);
            $result = $stmt->fetch();
            return $result['count'] ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get database charset
     */
    protected function getDatabaseCharset($database)
    {
        try {
            $pdo = $this->getRootConnection();
            $stmt = $pdo->prepare("
                SELECT DEFAULT_CHARACTER_SET_NAME as charset
                FROM information_schema.SCHEMATA
                WHERE SCHEMA_NAME = ?
            ");
            $stmt->execute([$database]);
            $result = $stmt->fetch();
            return $result['charset'] ?? 'utf8mb4';
        } catch (\Exception $e) {
            return 'utf8mb4';
        }
    }

    /**
     * List all database users
     */
    public function listUsers()
    {
        try {
            $pdo = $this->getRootConnection();
            $stmt = $pdo->query("
                SELECT User, Host
                FROM mysql.user
                WHERE User NOT IN ('mysql.sys', 'mysql.session', 'mysql.infoschema')
                ORDER BY User, Host
            ");
            $users = $stmt->fetchAll();

            $result = [];
            foreach ($users as $user) {
                // Count databases for this user
                // GRANTEE format is 'username'@'host'
                $grantee = "'" . $user['User'] . "'@'" . $user['Host'] . "'";
                $dbCountStmt = $pdo->prepare("
                    SELECT COUNT(DISTINCT TABLE_SCHEMA) as db_count
                    FROM information_schema.SCHEMA_PRIVILEGES
                    WHERE GRANTEE = ?
                ");
                $dbCountStmt->execute([$grantee]);
                $dbCount = $dbCountStmt->fetch();

                $result[] = [
                    'username' => $user['User'],
                    'host' => $user['Host'],
                    'database_count' => $dbCount['db_count'] ?? 0,
                ];
            }

            return $result;
        } catch (PDOException $e) {
            Log::error('Failed to list users: ' . $e->getMessage());
            throw new \Exception('Failed to list users: ' . $e->getMessage());
        }
    }

    /**
     * Create a new database user
     */
    public function createUser($username, $password, $host = 'localhost')
    {
        try {
            // Validate username
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
                throw new \Exception('Username can only contain letters, numbers, and underscores');
            }

            $pdo = $this->getRootConnection();
            $username = $pdo->quote($username);
            $host = $pdo->quote($host);
            $password = $pdo->quote($password);

            $pdo->exec("CREATE USER {$username}@{$host} IDENTIFIED BY {$password}");

            Log::info("Database user created: {$username}@{$host}");
            return true;
        } catch (PDOException $e) {
            Log::error('Failed to create user: ' . $e->getMessage());
            throw new \Exception('Failed to create user: ' . $e->getMessage());
        }
    }

    /**
     * Delete a database user
     */
    public function deleteUser($username, $host = 'localhost')
    {
        try {
            $pdo = $this->getRootConnection();
            $username = $pdo->quote($username);
            $host = $pdo->quote($host);

            $pdo->exec("DROP USER {$username}@{$host}");

            Log::info("Database user deleted: {$username}@{$host}");
            return true;
        } catch (PDOException $e) {
            Log::error('Failed to delete user: ' . $e->getMessage());
            throw new \Exception('Failed to delete user: ' . $e->getMessage());
        }
    }

    /**
     * Grant privileges to a user on a database
     */
    public function grantPrivileges($username, $database, $host = 'localhost', $privileges = 'ALL PRIVILEGES')
    {
        try {
            $pdo = $this->getRootConnection();

            // Check if user exists
            $checkUserStmt = $pdo->prepare("
                SELECT COUNT(*) as count 
                FROM mysql.user 
                WHERE User = ? AND Host = ?
            ");
            $checkUserStmt->execute([$username, $host]);
            $userExists = $checkUserStmt->fetch();

            if ($userExists['count'] == 0) {
                throw new \Exception("User '{$username}'@'{$host}' does not exist. Please create the user first.");
            }

            // Check if database exists
            $checkDbStmt = $pdo->prepare("
                SELECT COUNT(*) as count 
                FROM information_schema.SCHEMATA 
                WHERE SCHEMA_NAME = ?
            ");
            $checkDbStmt->execute([$database]);
            $dbExists = $checkDbStmt->fetch();

            if ($dbExists['count'] == 0) {
                throw new \Exception("Database '{$database}' does not exist.");
            }

            $username = $pdo->quote($username);
            $host = $pdo->quote($host);
            // Use backticks for database name (identifier)
            $dbName = '`' . str_replace('`', '``', $database) . '`';

            $pdo->exec("GRANT {$privileges} ON {$dbName}.* TO {$username}@{$host}");
            $pdo->exec("FLUSH PRIVILEGES");

            Log::info("Privileges granted: {$username}@{$host} on database: {$database}");
            return true;
        } catch (PDOException $e) {
            Log::error('Failed to grant privileges: ' . $e->getMessage());
            throw new \Exception('Failed to grant privileges: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Failed to grant privileges: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Revoke privileges from a user on a database
     */
    public function revokePrivileges($username, $database, $host = 'localhost')
    {
        try {
            $pdo = $this->getRootConnection();
            $username = $pdo->quote($username);
            $host = $pdo->quote($host);
            // Use backticks for database name (identifier)
            $dbName = '`' . str_replace('`', '``', $database) . '`';

            $pdo->exec("REVOKE ALL PRIVILEGES ON {$dbName}.* FROM {$username}@{$host}");
            $pdo->exec("FLUSH PRIVILEGES");

            Log::info("Privileges revoked: {$username}@{$host} on {$database}");
            return true;
        } catch (PDOException $e) {
            Log::error('Failed to revoke privileges: ' . $e->getMessage());
            throw new \Exception('Failed to revoke privileges: ' . $e->getMessage());
        }
    }

    /**
     * Get user privileges on a database
     */
    public function getUserPrivileges($username, $database, $host = 'localhost')
    {
        try {
            $pdo = $this->getRootConnection();
            $grantee = "'" . $username . "'@'" . $host . "'";
            $stmt = $pdo->prepare("
                SELECT * FROM information_schema.SCHEMA_PRIVILEGES
                WHERE GRANTEE = ?
                AND TABLE_SCHEMA = ?
            ");
            $stmt->execute([$grantee, $database]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            Log::error('Failed to get user privileges: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get database details including tables
     */
    public function getDatabaseDetails($database)
    {
        try {
            $pdo = $this->getRootConnection();

            // Get tables
            $stmt = $pdo->prepare("
                SELECT 
                    TABLE_NAME as `name`,
                    TABLE_ROWS as `table_rows`,
                    ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) as `size_mb`,
                    ENGINE as `engine`,
                    TABLE_COLLATION as `collation`
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = ?
                ORDER BY TABLE_NAME
            ");
            $stmt->execute([$database]);
            $tables = $stmt->fetchAll();

            // Rename table_rows to rows for consistency
            foreach ($tables as &$table) {
                $table['rows'] = $table['table_rows'];
                unset($table['table_rows']);
            }
            unset($table);

            // Get users with access
            $stmt = $pdo->prepare("
                SELECT DISTINCT GRANTEE as user
                FROM information_schema.SCHEMA_PRIVILEGES
                WHERE TABLE_SCHEMA = ?
            ");
            $stmt->execute([$database]);
            $userGrants = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Format users (remove quotes)
            $users = array_map(function ($grantee) {
                return trim($grantee, "'");
            }, $userGrants);

            return [
                'name' => $database,
                'size' => $this->getDatabaseSize($database),
                'tables' => $tables,
                'table_count' => count($tables),
                'users' => $users,
                'charset' => $this->getDatabaseCharset($database),
            ];
        } catch (PDOException $e) {
            Log::error('Failed to get database details: ' . $e->getMessage());
            throw new \Exception('Failed to get database details: ' . $e->getMessage());
        }
    }

    /**
     * Change user password
     */
    public function changeUserPassword($username, $newPassword, $host = 'localhost')
    {
        try {
            $pdo = $this->getRootConnection();
            $username = $pdo->quote($username);
            $host = $pdo->quote($host);
            $password = $pdo->quote($newPassword);

            $pdo->exec("ALTER USER {$username}@{$host} IDENTIFIED BY {$password}");

            Log::info("Password changed for user: {$username}@{$host}");
            return true;
        } catch (PDOException $e) {
            Log::error('Failed to change password: ' . $e->getMessage());
            throw new \Exception('Failed to change password: ' . $e->getMessage());
        }
    }
}