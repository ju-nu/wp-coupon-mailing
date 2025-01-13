<?php

use App\Config;
use App\Database;

require __DIR__ . '/../vendor/autoload.php';

Config::init();

$pdo = Database::connect();

// Check last 24 hours
$lastCheck = date('Y-m-d H:i:s', strtotime('-1 day'));

$sql = "
SELECT p.ID as coupon_id, tt.taxonomy, t.slug as brand_slug
FROM wp_posts p
INNER JOIN wp_term_relationships tr ON (p.ID = tr.object_id)
INNER JOIN wp_term_taxonomy tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)
INNER JOIN wp_terms t ON (tt.term_id = t.term_id)
WHERE p.post_type = 'coupon'
  AND p.post_status = 'publish'
  AND tt.taxonomy = 'coupon_store'
  AND p.post_date >= :lastCheck
";
$stmt = $pdo->prepare($sql);
$stmt->execute([':lastCheck' => $lastCheck]);
$newCoupons = $stmt->fetchAll();

if (!$newCoupons) {
    echo "No new coupons found.\n";
    exit;
}

$insertSql = "
INSERT INTO newsletter_coupon_queue (brand_slug, coupon_id, created_at)
VALUES (:brand, :cid, :cat)
";
$insertStmt = $pdo->prepare($insertSql);

foreach ($newCoupons as $coupon) {
    $insertStmt->execute([
        ':brand' => $coupon['brand_slug'],
        ':cid'   => $coupon['coupon_id'],
        ':cat'   => date('Y-m-d H:i:s'),
    ]);
}

echo "Queued " . count($newCoupons) . " coupons.\n";
