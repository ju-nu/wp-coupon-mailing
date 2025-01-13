-- schema.sql

-- Newsletter subscriptions table
CREATE TABLE IF NOT EXISTS newsletter_subscriptions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL,
  brand_slug VARCHAR(255) NOT NULL,
  status ENUM('pending','active') NOT NULL DEFAULT 'pending',
  confirm_token VARCHAR(255) NOT NULL,
  unsubscribe_token VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL,
  confirmed_at DATETIME NULL,
  UNIQUE KEY unique_subscription (email, brand_slug)
);

-- For daily queued coupons
CREATE TABLE IF NOT EXISTS newsletter_coupon_queue (
  id INT AUTO_INCREMENT PRIMARY KEY,
  brand_slug VARCHAR(255) NOT NULL,
  coupon_id INT NOT NULL,
  created_at DATETIME NOT NULL
);

-- Recommended indexes for performance
CREATE INDEX idx_email_brand ON newsletter_subscriptions (email, brand_slug);
CREATE INDEX idx_brand_status ON newsletter_subscriptions (brand_slug, status);
CREATE INDEX idx_created_at ON newsletter_subscriptions (created_at);
