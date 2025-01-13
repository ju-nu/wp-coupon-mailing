<?php

// scripts/send_coupons.php

use App\Config;
use App\Database;
use App\Mailer;
use App\Helpers;

require __DIR__ . '/../vendor/autoload.php';

Config::init();

$pdo = Database::connect();

$sql = "
SELECT brand_slug, GROUP_CONCAT(coupon_id) AS coupons
FROM newsletter_coupon_queue
GROUP BY brand_slug
";
$stmt = $pdo->query($sql);
$brandCoupons = $stmt->fetchAll();

if (!$brandCoupons) {
    echo "No coupons in queue.\n";
    exit;
}

foreach ($brandCoupons as $bc) {
    $brandSlug = $bc['brand_slug'];
    $couponIds = explode(',', $bc['coupons']);

    // Get brand subscribers
    $subSql = "SELECT email, unsubscribe_token FROM newsletter_subscriptions
               WHERE brand_slug = :brand AND status='active'";
    $subStmt = $pdo->prepare($subSql);
    $subStmt->execute([':brand' => $brandSlug]);
    $subscribers = $subStmt->fetchAll();

    if (!$subscribers) {
        continue;
    }

    // Prepare email body
    $templatePath = __DIR__ . '/../public/templates/brand_update_email.html';
    $bodyTemplate = file_get_contents($templatePath);

    $couponListHtml = '';
    foreach ($couponIds as $cid) {
        // Query WP for coupon title
        $wpSql = "SELECT post_title FROM wp_posts WHERE ID = :cid";
        $wpStmt = $pdo->prepare($wpSql);
        $wpStmt->execute([':cid' => $cid]);
        $row = $wpStmt->fetch();
        $title = $row ? $row['post_title'] : "Coupon #$cid";
        $permalink = "https://{$_SERVER['HTTP_HOST']}/?post_type=coupon&p={$cid}";
        $couponListHtml .= "<li><a href=\"$permalink\">$title</a></li>";
    }

    $couponCount = count($couponIds);
    $brandname = ucfirst($brandSlug); // or fetch official brand name from WP
    $bodyPrepared = str_replace('{{brandname}}', htmlspecialchars($brandname), $bodyTemplate);
    $bodyPrepared = str_replace('{{coupon_count}}', $couponCount, $bodyPrepared);
    $bodyPrepared = str_replace('{{coupon_list}}', $couponListHtml, $bodyPrepared);

    // Subject from template
    $subjectTpl = Config::get('NEWSLETTER_SUBJECT_TEMPLATE');
    $now = new \DateTime();
    $formattedDate = Helpers::formatGermanDate($now);

    // We’ll replace:
    // {brandname}, {count}, and {day}. {month} {year} (already combined in $formattedDate)
    $subject = str_replace(
        ['{brandname}', '{count}', '{day}. {month} {year}'],
        [$brandname, $couponCount, $formattedDate],
        $subjectTpl
    );

    foreach ($subscribers as $sub) {
        $token = $sub['unsubscribe_token'];
        $unsubscribeLink = "https://{$_SERVER['HTTP_HOST']}/unsubscribe.php?token={$token}";
        $bodyFinal = str_replace('{{unsubscribe_link}}', $unsubscribeLink, $bodyPrepared);
        Mailer::sendMail($sub['email'], $subject, $bodyFinal, $token);
    }

    echo "Sent $couponCount coupons update to " . count($subscribers) . " subscribers for brand $brandSlug.\n";
}

// Clear queue
$pdo->query("TRUNCATE TABLE newsletter_coupon_queue");
echo "Cleared coupon queue.\n";
