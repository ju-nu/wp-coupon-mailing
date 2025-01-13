<?php

use App\Config;
use App\Database;

require __DIR__ . '/../vendor/autoload.php';

Config::init();

$token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_STRING);
if (!$token) {
    echo "Ungültiger Token.";
    exit;
}

$pdo = Database::connect();
$sql = "SELECT id, status, brand_slug FROM newsletter_subscriptions WHERE confirm_token = :token LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([':token' => $token]);
$subscription = $stmt->fetch();

if (!$subscription) {
    echo "Token ungültig oder abgelaufen.";
    exit;
}

if ($subscription['status'] !== 'pending') {
    echo "Du hast bereits bestätigt.";
    exit;
}

$updateSql = "UPDATE newsletter_subscriptions
              SET status='active', confirmed_at = :cat
              WHERE id=:id";
$upStmt = $pdo->prepare($updateSql);
$upStmt->execute([':cat' => date('Y-m-d H:i:s'), ':id' => $subscription['id']]);

$template = file_get_contents(__DIR__ . '/templates/confirm_success.html');
$template = str_replace('{{brand_slug}}', htmlspecialchars($subscription['brand_slug']), $template);
echo $template;
