<?php

namespace App;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    public static function sendMail(
        string $toEmail,
        string $subject,
        string $bodyHtml,
        string $unsubscribeToken = ''
    ): bool {
        $host     = Config::get('SMTP_HOST');
        $port     = Config::get('SMTP_PORT');
        $username = Config::get('SMTP_USER');
        $password = Config::get('SMTP_PASS');
        $from     = Config::get('SMTP_FROM');
        $fromName = Config::get('SMTP_FROM_NAME');

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = $host;
            $mail->Port       = $port;
            $mail->SMTPAuth   = true;
            $mail->Username   = $username;
            $mail->Password   = $password;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

            $mail->setFrom($from, $fromName);
            $mail->addAddress($toEmail);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $bodyHtml;

            // For Postmark, optionally:
            // $mail->addCustomHeader('X-PM-Message-Stream', 'broadcast');

            $mail->send();
            return true;
        } catch (Exception $e) {
            // Log or handle error
            return false;
        }
    }

    /**
     * New method: send up to 500 emails in a single batch using Postmark's API
     * @param array $messages Each array item is a single email structure:
     * [
     *   'To'      => 'recipient@example.com',
     *   'Subject' => 'Subject Here',
     *   'HtmlBody'=> '<html> ... </html>',
     *   'MessageStream' => 'broadcast' (optional),
     *   'From'    => 'sender@domain.com'
     *   ...
     * ]
     */
    public static function sendMailBatch(array $messages): bool
    {
        $apiToken = Config::get('SMTP_USER');  // or a dedicated POSTMARK_SERVER_TOKEN if you prefer
        $apiUrl   = 'https://api.postmarkapp.com/email/batch';

        // Prepare cURL
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($messages));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Set Postmark headers
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
            // Log or handle error
            return false;
        }
        return true;
    }
}
