<?php

use App\Config;
use App\Database;
use App\WPDatabase;
use App\Mailer;
use App\Helpers;

require __DIR__ . '/../vendor/autoload.php';

/**
 * This script runs at 10 AM:
 *  - Reads all queued coupons from the newsletter DB
 *  - Groups by brand
 *  - Fetches each coupon's title from the WP DB
 *  - Sends out brand-based emails
 *  - Clears the queue
 */

Config::init();

// 1) Connect to the newsletter DB to get queued coupons
$newsletterPdo = Database::connect();

$sql = "
SELECT brand_slug, GROUP_CONCAT(coupon_id) AS coupons
FROM newsletter_coupon_queue
GROUP BY brand_slug
";
$stmt = $newsletterPdo->query($sql);
$brandCoupons = $stmt->fetchAll();

if (!$brandCoupons) {
    echo "No coupons in the queue.\n";
    exit;
}

// 2) Connect to WP DB for coupon titles
$wpPdo = WPDatabase::connect();

$bodyTemplate = file_get_contents(__DIR__ . '/../public/templates/brand_update_email.html');
$subjectTpl   = Config::get('NEWSLETTER_SUBJECT_TEMPLATE');
$now          = new \DateTime();
$formattedDate = Helpers::formatGermanDate($now);

foreach ($brandCoupons as $bc) {
    $brandSlug = $bc['brand_slug'];
    $couponIds = explode(',', $bc['coupons']);

    if (empty($couponIds)) {
        continue;
    }

    // Build HTML for these coupons (get titles from WP)
    $couponListHtml = '';
    foreach ($couponIds as $cid) {
        $wpSql = "SELECT post_title FROM wp_posts WHERE ID = :cid LIMIT 1";
        $wpStmt = $wpPdo->prepare($wpSql);
        $wpStmt->execute([':cid' => $cid]);
        $row = $wpStmt->fetch();

        $title = $row ? $row['post_title'] : "Coupon #$cid";
        $link  = "https://vorteilplus.de/?post_type=coupon&p={$cid}";
        $couponListHtml .= "<li><a href=\"$link\">$title</a></li>";
    }

    $couponCount = count($couponIds);
    $brandname = ucfirst($brandSlug);

    // e.g. "Adidas: Jetzt 2 neue Gutscheine für 19. Januar 2025"
    $subject = str_replace(
        ['{brandname}', '{count}', '{day}. {month} {year}'],
        [$brandname, $couponCount, $formattedDate],
        $subjectTpl
    );

    // 3) Get brand subscribers from newsletter DB
    $subSql = "SELECT email, unsubscribe_token FROM newsletter_subscriptions
               WHERE brand_slug = :brand AND status='active'";
    $subStmt = $newsletterPdo->prepare($subSql);
    $subStmt->execute([':brand' => $brandSlug]);
    $subscribers = $subStmt->fetchAll();
    $numSubs = count($subscribers);

    if ($numSubs < 1) {
        continue;
    }

    echo "Sending coupons for brand '{$brandSlug}' to {$numSubs} subscribers...\n";

    // 4) Build batch array & send (example: up to 500 at a time)
    $batch = [];
    $batchSize = 0;
    $batchMax = 500;
    $sleepTime = 5; // seconds to sleep between batches

    foreach ($subscribers as $sub) {
        $unsubscribeLink = "https://vorteilplus.de/unsubscribe.php?token={$sub['unsubscribe_token']}";

        $body = str_replace('{{brandname}}', htmlspecialchars($brandname), $bodyTemplate);
        $body = str_replace('{{coupon_count}}', $couponCount, $body);
        $body = str_replace('{{coupon_list}}', $couponListHtml, $body);
        $body = str_replace('{{unsubscribe_link}}', $unsubscribeLink, $body);

        $batch[] = [
            'From'     => Config::get('POSTMARK_SENDER'),
            'To'       => $sub['email'],
            'Subject'  => $subject,
            'HtmlBody' => $body,
            'MessageStream' => Config::get('POSTMARK_CHANNEL_BROADCAST')
        ];
        $batchSize++;

        if ($batchSize === $batchMax) {
            Mailer::sendMailBatch($batch);
            $batch = [];
            $batchSize = 0;
            sleep($sleepTime);
        }
    }

    // leftover
    if ($batchSize > 0) {
        Mailer::sendMailBatch($batch);
        sleep($sleepTime);
    }
}

// 5) Clear the queue
$newsletterPdo->query("TRUNCATE TABLE newsletter_coupon_queue");
echo "Queue cleared.\n";
