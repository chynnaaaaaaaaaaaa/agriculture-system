<?php
declare(strict_types=1);


class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s;sslmode=%s',
            env('DB_HOST'),
            env('DB_PORT', '5432'),
            env('DB_NAME', 'postgres'),
            env('DB_SSLMODE', 'require')      // Supabase requires SSL
        );

        try {
            self::$pdo = new PDO($dsn, env('DB_USER'), env('DB_PASSWORD'), [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            error_log('DB connection failed: ' . $e->getMessage());   // details go to the log only
            http_response_code(500);
            exit('The system cannot reach the database right now. Please contact the administrator.');
        }
        return self::$pdo;
    }
}
