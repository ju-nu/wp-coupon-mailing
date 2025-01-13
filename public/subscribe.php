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

if (!ReCaptcha::verify($recaptchaToken)) {
    echo json_encode(['success' => false, 'message' => 'reCAPTCHA fehlgeschlagen.']);
    exit;
}

try {
    $pdo = Database::connect();
    $checkSql = "SELECT id, status FROM newsletter_subscriptions WHERE email = :email AND brand_slug = :brand";
    $stmt = $pdo->prepare($checkSql);
    $stmt->execute([':email' => $email, ':brand' => $brandSlug]);
    $existing = $stmt->fetch();

    if ($existing && $existing['status'] === 'active') {
        echo json_encode(['success' => true, 'message' => 'Du bist bereits für diesen Brand angemeldet.']);
        exit;
    }

    $confirmToken = Helpers::generateToken(16);
    $unsubscribeToken = Helpers::generateToken(16);
    $createdAt = date('Y-m-d H:i:s');

    if ($existing) {
        // Update existing
        $updateSql = "UPDATE newsletter_subscriptions
                      SET status = 'pending', confirm_token = :ctoken, unsubscribe_token = :utoken, created_at = :cat, confirmed_at = NULL
                      WHERE id = :id";
        $upStmt = $pdo->prepare($updateSql);
        $upStmt->execute([
            ':ctoken' => $confirmToken,
            ':utoken' => $unsubscribeToken,
            ':cat' => $createdAt,
            ':id' => $existing['id']
        ]);
    } else {
        // Insert new
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
    }

    // Send double opt-in email
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
