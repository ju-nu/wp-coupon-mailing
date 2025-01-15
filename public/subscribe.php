<?php

use App\Config;
use App\Database;
use App\Helpers;
use App\Mailer;

require __DIR__ . '/../vendor/autoload.php';

Config::init();

header('Content-Type: application/json; charset=UTF-8');

$email      = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$brandSlug  = filter_input(INPUT_POST, 'brand_slug', FILTER_SANITIZE_STRING);
$turnstile  = filter_input(INPUT_POST, 'cf-turnstile-response', FILTER_SANITIZE_STRING);

if (!$email || !$brandSlug) {
    echo json_encode(['success' => false, 'message' => 'Ungültige Eingaben.']);
    exit;
}

if (!$turnstile) {
    echo json_encode(['success' => false, 'message' => 'Turnstile-Token fehlt.']);
    exit;
}

$turnstileSecretKey = Config::get('TURNSTILE_SECRET_KEY');
$verifyURL          = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

// Prepare POST data
$postData = http_build_query([
    'secret'   => $turnstileSecretKey,
    'response' => $turnstile,
    'remoteip' => $_SERVER['REMOTE_ADDR'] ?? null
]);

$options = [
    'http' => [
        'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => $postData
    ]
];

$context   = stream_context_create($options);
$response  = file_get_contents($verifyURL, false, $context);
$captcha   = json_decode($response, true);

if (!$captcha || !$captcha['success']) {
    echo json_encode(['success' => false, 'message' => 'Cloudflare Turnstile-Überprüfung fehlgeschlagen.']);
    exit;
}

try {
    $pdo = Database::connect();
    // Check if there's already a row for this email+brand
    $checkSql = "SELECT id, status FROM newsletter_subscriptions
                 WHERE email = :email AND brand_slug = :brand
                 LIMIT 1";
    $stmt = $pdo->prepare($checkSql);
    $stmt->execute([':email' => $email, ':brand' => $brandSlug]);
    $existing = $stmt->fetch();

    if ($existing) {
        if ($existing['status'] === 'active') {
            echo json_encode(['success' => false, 'message' => 'Du bist bereits für diesen Brand angemeldet.']);
            exit;
        } elseif ($existing['status'] === 'pending') {
            echo json_encode([
                'success' => false,
                'message' => 'Diese Adresse wartet noch auf Bestätigung. Bitte prüfe dein E-Mail-Postfach.'
            ]);
            exit;
        }
    }

    $confirmToken     = Helpers::generateToken(16);
    $unsubscribeToken = Helpers::generateToken(16);
    $createdAt        = date('Y-m-d H:i:s');

    $insertSql = "INSERT INTO newsletter_subscriptions
                  (email, brand_slug, status, confirm_token, unsubscribe_token, created_at)
                  VALUES (:email, :brand, 'pending', :ctoken, :utoken, :cat)";
    $inStmt = $pdo->prepare($insertSql);
    $inStmt->execute([
        ':email'  => $email,
        ':brand'  => $brandSlug,
        ':ctoken' => $confirmToken,
        ':utoken' => $unsubscribeToken,
        ':cat'    => $createdAt
    ]);

    // Send double opt-in email
    $confirmLink = "https://mailing.vorteilplus.de/confirm.php?token={$confirmToken}";
    $body = file_get_contents(__DIR__ . '/templates/double_optin_email.html');
    $body = str_replace('{{confirm_link}}', $confirmLink, $body);
    $body = str_replace('{{brand_slug}}', htmlspecialchars($brandSlug), $body);

    $subject = "Bitte bestätige dein Abonnement für {$brandSlug}";
    Mailer::sendMailBatch([[
        'From'          => Config::get('POSTMARK_SENDER'),
        'To'            => $email,
        'Subject'       => $subject,
        'HtmlBody'      => $body,
        'MessageStream' => Config::get('POSTMARK_CHANNEL_TRANSACTIONAL')
    ]]);

    echo json_encode([
        'success' => true,
        'message' => 'Bitte bestätige deine Anmeldung über den Link in der E-Mail.'
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Fehler beim Speichern.']);
}
