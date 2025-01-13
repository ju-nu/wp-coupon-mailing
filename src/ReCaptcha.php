<?php

namespace App;

class ReCaptcha
{
    public static function verify(string $token): bool
    {
        $secretKey = Config::get('RECAPTCHA_SECRET_KEY');
        if (!$secretKey || !$token) {
            return false;
        }

        $response = file_get_contents(
            "https://www.google.com/recaptcha/api/siteverify?secret={$secretKey}&response={$token}"
        );
        $result = json_decode($response, true);

        return !empty($result['success']) && $result['success'] === true;
    }
}
