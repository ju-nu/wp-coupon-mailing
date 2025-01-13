<?php

use App\Config;
use App\Database;
use App\Mailer;
use App\Helpers;

require __DIR__ . '/../vendor/autoload.php';

Config::init();
$pdo = Database::connect();

// 1. Fetch queued coupons grouped by brand
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

// Pre-load the brand update template
$bodyTemplate = file_get_contents(__DIR__ . '/../public/templates/brand_update_email.html');
$subjectTpl   = Config::get('NEWSLETTER_SUBJECT_TEMPLATE');
$now          = new \DateTime();
$formattedDate = Helpers::formatGermanDate($now);

// We'll chunk the subscriber queries to handle high volume.
$chunkSize = 10_000;
$batchMax  = 500;
$sleepTime = 5; // seconds after each batch

foreach ($brandCoupons as $bc) {
    $brandSlug = $bc['brand_slug'];
    $couponIds = explode(',', $bc['coupons']);

    // Build coupon list
    $couponListHtml = '';
    foreach ($couponIds as $cid) {
        $wpSql = "SELECT post_title FROM wp_posts WHERE ID = :cid";
        $wpStmt = $pdo->prepare($wpSql);
        $wpStmt->execute([':cid' => $cid]);
        $row = $wpStmt->fetch();
        $title = $row ? $row['post_title'] : "Coupon #$cid";
        $link  = "https://{$_SERVER['HTTP_HOST']}/?post_type=coupon&p={$cid}";
        $couponListHtml .= "<li><a href=\"$link\">$title</a></li>";
    }

    $couponCount = count($couponIds);
    if ($couponCount < 1) {
        continue;
    }

    // Prepare subject
    $brandname = ucfirst($brandSlug);
    $subject = str_replace(
        ['{brandname}', '{count}', '{day}. {month} {year}'],
        [$brandname, $couponCount, $formattedDate],
        $subjectTpl
    );

    echo "Sending coupons for brand '{$brandSlug}'...\n";

    // We'll query subscribers in a loop with offset
    $offset = 0;
    $totalSent = 0;

    do {
        $subSql = "
            SELECT email, unsubscribe_token
            FROM newsletter_subscriptions
            WHERE brand_slug = :brand
              AND status='active'
            ORDER BY id
            LIMIT :offset, :chunk
        ";
        $subStmt = $pdo->prepare($subSql);
        $subStmt->bindValue(':brand', $brandSlug);
        $subStmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $subStmt->bindValue(':chunk', $chunkSize, \PDO::PARAM_INT);
        $subStmt->execute();
        $subscribers = $subStmt->fetchAll();
        $countCurrent = count($subscribers);

        if ($countCurrent === 0) {
            break;
        }

        $offset += $chunkSize;

        // Now batch these subscribers
        $batch = [];
        $batchSize = 0;

        foreach ($subscribers as $sub) {
            $unsubscribeLink = "https://{$_SERVER['HTTP_HOST']}/unsubscribe.php?token={$sub['unsubscribe_token']}";

            // Fill placeholders
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

            // If we hit 500, send
            if ($batchSize === $batchMax) {
                $ok = Mailer::sendMailBatch($batch);
                if (!$ok) {
                    echo "Error sending batch for brand {$brandSlug}\n";
                }
                $totalSent += $batchSize;
                $batch = [];
                $batchSize = 0;
                // Sleep to avoid rate-limit
                sleep($sleepTime);
            }
        }

        // Any remainder
        if ($batchSize > 0) {
            $ok = Mailer::sendMailBatch($batch);
            if (!$ok) {
                echo "Error sending final batch for brand {$brandSlug}\n";
            }
            $totalSent += $batchSize;
            $batch = [];
            $batchSize = 0;
            sleep($sleepTime);
        }

    } while ($countCurrent > 0);

    echo "Finished sending brand '{$brandSlug}' to total {$totalSent} subscribers.\n";
}

// Clear the queue
$pdo->query("TRUNCATE TABLE newsletter_coupon_queue");
echo "Cleared coupon queue.\n";
