<?php

use App\Config;
use App\Database;

require __DIR__ . '/../vendor/autoload.php';

Config::init();

$token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_STRING);
if (!$token) {
    echo "Ungültiger Unsubscribe-Token.";
    exit;
}

$pdo = Database::connect();
$sql = "SELECT id, email, brand_slug FROM newsletter_subscriptions WHERE unsubscribe_token = :token LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([':token' => $token]);
$subscription = $stmt->fetch();

if (!$subscription) {
    echo "Token ungültig.";
    exit;
}

// Count how many active for this email
$sqlCount = "SELECT COUNT(*) as total FROM newsletter_subscriptions WHERE email = :email AND status='active'";
$cStmt = $pdo->prepare($sqlCount);
$cStmt->execute([':email' => $subscription['email']]);
$countRow = $cStmt->fetch();
$totalActive = (int)$countRow['total'];

if ($totalActive <= 1) {
    // Remove entire email
    $delSql = "DELETE FROM newsletter_subscriptions WHERE email = :email";
    $delStmt = $pdo->prepare($delSql);
    $delStmt->execute([':email' => $subscription['email']]);
} else {
    // Remove just this brand
    $delSql = "DELETE FROM newsletter_subscriptions WHERE id = :id";
    $delStmt = $pdo->prepare($delSql);
    $delStmt->execute([':id' => $subscription['id']]);
}

$template = file_get_contents(__DIR__ . '/templates/unsubscribe_success.html');
$template = str_replace('{{brand_slug}}', htmlspecialchars($subscription['brand_slug']), $template);
echo $template;
