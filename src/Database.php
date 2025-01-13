<?php

namespace App;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $pdo = null;

    public static function connect(): PDO
    {
        if (self::$pdo === null) {
            try {
                $host = Config::get('DB_HOST');
                $db   = Config::get('DB_NAME');
                $user = Config::get('DB_USER');
                $pass = Config::get('DB_PASS');
                $charset = Config::get('DB_CHARSET', 'utf8mb4');

                $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
                self::$pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            } catch (PDOException $e) {
                throw new \Exception('DB Connection Error: ' . $e->getMessage());
            }
        }
        return self::$pdo;
    }
}
