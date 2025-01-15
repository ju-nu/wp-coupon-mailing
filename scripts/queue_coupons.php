<?php

use App\Config;
use App\WPDatabase;
use App\Database;

require __DIR__ . '/../vendor/autoload.php';

/**
 * This script runs (e.g. at 4 AM) to queue newly published coupons from WP 
 * into the newsletter DB. It also supports an "init import" mode that imports 
 * all published coupons, ignoring any date range.
 *
 * Usage:
 *   # Normal daily import (last 24 hours):
 *   php queue_coupons.php
 *
 *   # Initial import of ALL published coupons:
 *   php queue_coupons.php --init
 */

Config::init();

// 1) Detect if we're in init mode
$initImport = in_array('--init', $argv, true);

// 2) Connect to WP DB
$wpPdo = WPDatabase::connect();

// Build the WHERE clause depending on init or daily mode
$whereClause = "
  p.post_type = 'coupon'
  AND p.post_status = 'publish'
  AND tt.taxonomy = 'coupon_store'
";

if (!$initImport) {
    // Normal daily import => last 24 hours
    $lastCheck = date('Y-m-d H:i:s', strtotime('-1 day'));

    $sql = "
    SELECT p.ID as coupon_id, tt.taxonomy, t.slug as brand_slug
    FROM wp_posts p
    INNER JOIN wp_term_relationships tr ON p.ID = tr.object_id
    INNER JOIN wp_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
    INNER JOIN wp_terms t ON tt.term_id = t.term_id
    WHERE $whereClause
      AND p.post_date >= :lastCheck
    ";
    $stmt = $wpPdo->prepare($sql);
    $stmt->execute([':lastCheck' => $lastCheck]);
} else {
    // Init import => no date limit
    $sql = "
    SELECT p.ID as coupon_id, tt.taxonomy, t.slug as brand_slug
    FROM wp_posts p
    INNER JOIN wp_term_relationships tr ON p.ID = tr.object_id
    INNER JOIN wp_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
    INNER JOIN wp_terms t ON tt.term_id = t.term_id
    WHERE $whereClause
    ";
    $stmt = $wpPdo->prepare($sql);
    $stmt->execute();
}

$newCoupons = $stmt->fetchAll();
$count = count($newCoupons);

if ($count === 0) {
    if ($initImport) {
        echo "No coupons found in WP (init import mode).\n";
    } else {
        echo "No new coupons found in WP (daily mode).\n";
    }
    exit;
}

// 3) Insert them into the newsletter DB's queue
$newsletterPdo = Database::connect(); // newsletter DB
$insertSql = "
INSERT INTO newsletter_coupon_queue (brand_slug, coupon_id, created_at)
VALUES (:brand, :cid, :cat)
";
$insertStmt = $newsletterPdo->prepare($insertSql);

$rowsInserted = 0;
foreach ($newCoupons as $coupon) {
    echo $coupon['brand_slug'];
    $insertStmt->execute([
        ':brand' => $coupon['brand_slug'],
        ':cid'   => $coupon['coupon_id'],
        ':cat'   => date('Y-m-d H:i:s'),
    ]);
    $rowsInserted++;
}

// 4) Done
if ($initImport) {
    echo "INIT IMPORT: Queued {$rowsInserted} coupons.\n";
} else {
    echo "DAILY IMPORT: Queued {$rowsInserted} new coupons (last 24h).\n";
}
