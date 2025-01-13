<?php

namespace App;

class Auth
{
    public static function isLoggedIn(): bool
    {
        session_start();
        return !empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
    }

    public static function login(string $username, string $password): bool
    {
        $envUser = Config::get('ADMIN_USER');
        $envPassHash = Config::get('ADMIN_PASS_HASH');

        if ($username === $envUser && password_verify($password, $envPassHash)) {
            session_regenerate_id(true);
            $_SESSION['admin_logged_in'] = true;
            return true;
        }
        return false;
    }

    public static function logout(): void
    {
        session_start();
        session_destroy();
    }
}
