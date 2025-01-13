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
