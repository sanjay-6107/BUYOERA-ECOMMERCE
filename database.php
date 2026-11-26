<?php
/**
 * Database Connection & Query Helper
 * BUYOERA E-Commerce Architecture
 * Supports PDO MySQL (Primary) with SQLite auto-fallback for seamless local testing.
 */

require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $instance = null;
    private static string $driverUsed = 'mysql';

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            self::connect();
        }
        return self::$instance;
    }

    public static function getDriver(): string {
        return self::$driverUsed;
    }

    private static function connect(): void {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        // 1. Try MySQL First if configured
        if (DB_DRIVER === 'mysql') {
            try {
                $dsn = sprintf(
                    "mysql:host=%s;port=%s;dbname=%s;charset=%s",
                    DB_HOST,
                    DB_PORT,
                    DB_NAME,
                    DB_CHARSET
                );
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
                self::$driverUsed = 'mysql';
                return;
            } catch (PDOException $e) {
                // If MySQL connection fails (e.g. server stopped), fallback gracefully to SQLite for local development
                error_log("MySQL connection failed: " . $e->getMessage() . ". Falling back to local SQLite database.");
            }
        }

        // 2. SQLite Fallback
        try {
            $sqlitePath = dirname(__DIR__) . '/database/buyoera.sqlite';
            $firstInit = !file_exists($sqlitePath);
            self::$instance = new PDO("sqlite:" . $sqlitePath, null, null, $options);
            self::$instance->exec("PRAGMA foreign_keys = ON;");
            self::$driverUsed = 'sqlite';

            if ($firstInit || filesize($sqlitePath) < 1000) {
                // Auto-seed sqlite database if freshly created
                require_once dirname(__DIR__) . '/database/seed_data.php';
                buyoera_run_seeds(self::$instance, 'sqlite');
            }
        } catch (PDOException $e) {
            die("<div style='font-family:sans-serif;padding:30px;color:#721c24;background:#f8d7da;border:1px solid #f5c6cb;border-radius:8px;max-width:600px;margin:50px auto;'>" .
                "<h2>BUYOERA Database Connection Error</h2>" .
                "<p>Could not connect to MySQL or initialize local database.</p>" .
                "<p><strong>Details:</strong> " . htmlspecialchars($e->getMessage()) . "</p>" .
                "<p>Please ensure MySQL is running or verify your credentials in <code>config/config.php</code>.</p>" .
                "</div>");
        }
    }
}
