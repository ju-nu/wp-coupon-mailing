<?php

use App\Config;
use App\Database;
use App\ReCaptcha;
use App\Helpers;
use App\Mailer;

require __DIR__ . '/../vendor/autoload.php';

Config::init();

header('Content-Type: application/json; charset=UTF-8');

$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$brandSlug = filter_input(INPUT_POST, 'brand_slug', FILTER_SANITIZE_STRING);
$recaptchaToken = filter_input(INPUT_POST, 'g-recaptcha-response', FILTER_SANITIZE_STRING);

if (!$email || !$brandSlug) {
    echo json_encode(['success' => false, 'message' => 'Ungültige Eingaben.']);
    exit;
}

// reCAPTCHA check
if (!ReCaptcha::verify($recaptchaToken)) {
    echo json_encode(['success' => false, 'message' => 'reCAPTCHA fehlgeschlagen.']);
    exit;
}

try {
    $pdo = Database::connect();
    // Look for ANY existing record for this brand/email
    $checkSql = "SELECT id, status FROM newsletter_subscriptions 
                 WHERE email = :email AND brand_slug = :brand 
                 LIMIT 1";
    $stmt = $pdo->prepare($checkSql);
    $stmt->execute([':email' => $email, ':brand' => $brandSlug]);
    $existing = $stmt->fetch();

    if ($existing) {
        if ($existing['status'] === 'active') {
            echo json_encode([
                'success' => false,
                'message' => 'Du bist bereits für diesen Brand angemeldet.'
            ]);
            exit;
        } elseif ($existing['status'] === 'pending') {
            echo json_encode([
                'success' => false,
                'message' => 'Deine Anmeldung für diesen Brand wird bereits bestätigt. Sieh bitte in deinem E-Mail-Postfach nach.'
            ]);
            exit;
        }
        // If, for some reason, status is something else, we can also handle it
    }

    // Insert new or re-insert pending
    $confirmToken = Helpers::generateToken(16);
    $unsubscribeToken = Helpers::generateToken(16);
    $createdAt = date('Y-m-d H:i:s');

    $insertSql = "INSERT INTO newsletter_subscriptions
                  (email, brand_slug, status, confirm_token, unsubscribe_token, created_at)
                  VALUES (:email, :brand, 'pending', :ctoken, :utoken, :cat)";
    $inStmt = $pdo->prepare($insertSql);
    $inStmt->execute([
        ':email' => $email,
        ':brand' => $brandSlug,
        ':ctoken' => $confirmToken,
        ':utoken' => $unsubscribeToken,
        ':cat' => $createdAt
    ]);

    // Double opt-in email
    $confirmLink = "https://{$_SERVER['HTTP_HOST']}/confirm.php?token={$confirmToken}";
    $templatePath = __DIR__ . '/templates/double_optin_email.html';
    $body = file_get_contents($templatePath);
    $body = str_replace('{{confirm_link}}', $confirmLink, $body);
    $body = str_replace('{{brand_slug}}', htmlspecialchars($brandSlug), $body);

    $subject = "Bitte bestätige dein Abonnement für {$brandSlug}";
    Mailer::sendMail($email, $subject, $body);

    echo json_encode(['success' => true, 'message' => 'Bitte bestätige deine Anmeldung über den Link in der E-Mail.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Fehler beim Speichern.']);
}
