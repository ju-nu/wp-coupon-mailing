<?php

namespace App;

class Mailer
{
    /**
     * Send up to 500 emails in one call to Postmark's /email/batch endpoint.
     * Each array item in $messages is:
     * [
     *   'From'      => "no-reply@yourdomain.com",
     *   'To'        => "someone@example.com",
     *   'Subject'   => "Subject",
     *   'HtmlBody'  => "<html>...</html>",
     *   // optional: 'MessageStream' => "broadcast",
     * ]
     */
    public static function sendMailBatch(array $messages): bool
    {
        $apiToken = Config::get('POSTMARK_SERVER_TOKEN'); 
        $apiUrl   = 'https://api.postmarkapp.com/email/batch';

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($messages));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Accept: application/json",
            "Content-Type: application/json",
            "X-Postmark-Server-Token: {$apiToken}"
        ]);

        $response = curl_exec($ch);
        $error    = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error || $httpCode >= 400) {
            // Log or handle error if needed
            return false;
        }
        return true;
    }
}
