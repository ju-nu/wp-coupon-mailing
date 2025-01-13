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
}
