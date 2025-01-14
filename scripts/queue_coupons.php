<?php

use App\Config;
use App\WPDatabase;
use App\Database;

require __DIR__ . '/../vendor/autoload.php';

/**
 * This script runs at (e.g.) 4 AM to find newly published coupons in WP,
 * then insert them into the newsletter DB queue.
 */

Config::init();

// 1) Connect to WP DB to find new coupons
$wpPdo = WPDatabase::connect();

// Example approach: check last 24 hours
$lastCheck = date('Y-m-d H:i:s', strtotime('-1 day'));

// Query newly published coupons in WP
$sql = "
SELECT p.ID as coupon_id, tt.taxonomy, t.slug as brand_slug
FROM wp_posts p
INNER JOIN wp_term_relationships tr ON p.ID = tr.object_id
INNER JOIN wp_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
INNER JOIN wp_terms t ON tt.term_id = t.term_id
WHERE p.post_type = 'coupon'
  AND p.post_status = 'publish'
  AND tt.taxonomy = 'coupon_store'
  AND p.post_date >= :lastCheck
";
$stmt = $wpPdo->prepare($sql);
$stmt->execute([':lastCheck' => $lastCheck]);
$newCoupons = $stmt->fetchAll();

if (!$newCoupons) {
    echo "No new coupons found in WP.\n";
    exit;
}

// 2) Insert them into the newsletter DB's queue
$newsletterPdo = Database::connect(); // newsletter DB
$insertSql = "INSERT INTO newsletter_coupon_queue (brand_slug, coupon_id, created_at)
              VALUES (:brand, :cid, :cat)";
$insertStmt = $newsletterPdo->prepare($insertSql);

$count = 0;
foreach ($newCoupons as $coupon) {
    $insertStmt->execute([
        ':brand' => $coupon['brand_slug'],
        ':cid'   => $coupon['coupon_id'],
        ':cat'   => date('Y-m-d H:i:s'),
    ]);
    $count++;
}

echo "Queued {$count} new coupons.\n";
