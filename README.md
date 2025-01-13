# WP-Coupon-Mailing

This repository provides a **standalone (middleware) newsletter system** written in **PHP 8.3** that works alongside a WordPress site hosting coupon posts. It handles:

- **Double Opt-In** subscriptions (per brand)  
- **Unsubscribe** logic  
- **Daily newsletter emails** for new coupons in each brand  
- **Admin panel** to manage subscribers  
- **reCAPTCHA v3** to prevent spam signups  
- **Postmark SMTP** for sending emails  
- Scheduled using **Supervisor** (two daily jobs)

## Requirements

- **PHP 8.3+**  
- **MySQL 5.7+** (or equivalent)  
- **Composer** (to install dependencies)  
- Access to your **WordPress database** (or a separate DB if you prefer)  
- **Supervisor** or **Cron** for scheduling jobs  
- A valid **Postmark SMTP** account (with credentials in `.env`)

## Setup Steps

1. **Clone the repo**  
   ```bash
   git clone https://github.com/ju-nu/wp-coupon-mailing.git
   ```

2. **Install dependencies**  
   ```bash
   cd wp-coupon-mailing
   composer install
   ```
   Make sure you have `php-xml`, `php-mbstring`, etc. installed if needed.

3. **Create `.env` from `.env.example`**
   ```bash
   cp .env.example .env
   ```
   - Fill in your **DB credentials** (matching WP DB or separate DB).
   - Fill in **reCAPTCHA v3** keys.
   - Fill in **Postmark** SMTP credentials.
   - Provide an **ADMIN_USER** and a **ADMIN_PASS_HASH**.
  
4. **Database Tables**
   - Run schema.sql against your DB:
   ```bash
   mysql -u root -p your_db < schema.sql
   ```
   - Adjust table names if needed.

5. **Configure Nginx**
6. 