<?php

namespace App;

use PDO;
use PDOException;

/**
 * Dedicated class for connecting to the WordPress DB.
 * We read WordPress coupons/vouchers from here.
 */
class WPDatabase
{
    private static ?PDO $pdo = null;

    public static function connect(): PDO
    {
        if (self::$pdo === null) {
            try {
                $host = Config::get('WP_DB_HOST');
                $db   = Config::get('WP_DB_NAME');
                $user = Config::get('WP_DB_USER');
                $pass = Config::get('WP_DB_PASS');
                $charset = Config::get('WP_DB_CHARSET', 'utf8mb4');

                $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
                self::$pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            } catch (PDOException $e) {
                throw new \Exception('WordPress DB Connection Error: ' . $e->getMessage());
            }
        }
        return self::$pdo;
    }
}
