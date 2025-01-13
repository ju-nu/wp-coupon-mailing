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

-- Optional coupon queue table for daily updates
CREATE TABLE IF NOT EXISTS newsletter_coupon_queue (
  id INT AUTO_INCREMENT PRIMARY KEY,
  brand_slug VARCHAR(255) NOT NULL,
  coupon_id INT NOT NULL,
  created_at DATETIME NOT NULL
);
