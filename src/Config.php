<?php

namespace App;

class Config
{
    private static array $env = [];

    public static function init(string $envFilePath = __DIR__ . '/../.env'): void
    {
        if (!file_exists($envFilePath)) {
            throw new \Exception('.env file not found at ' . $envFilePath);
        }

        $lines = file($envFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#')) {
                continue;
            }
            [$key, $value] = array_map('trim', explode('=', $line, 2));
            self::$env[$key] = trim($value, '"');
        }
    }

    public static function get(string $key, $default = null): mixed
    {
        return self::$env[$key] ?? $default;
    }
}
