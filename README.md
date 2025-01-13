# WP-Coupon-Mailing

This repository provides a **)standalone (middleware)`** newsletter system in **PHP 8.3** that integrates with a WordPress site and is fully capable of:

- **Double Opt-In** subscriptions (one brand per subscription record) -- handles the **"Do you want to subscribe to this brand?"**
- **Unsubscribe** logic (per brand, or remove the user if( no other brands subscriptions)) -- in compliance with DSGVO+GDPR
- **Job-based newsletter** for new vouchers being published in WordPress (mail once at daily)

- **Admin panel** to manage growting lists(search, sort, paginate), and manual ad/remove subscribers

- **reCAPTCHa v3** to prevent spam signups
- **Postmark** for high-performance batch-sending (up to 500 messages per call)

- **Supervisor** (two daily jobs) -- 4 AM for queuing new coupons, 10 AM for sending emails
(* To avoid application overloading amounts of constant emails),in case you need to schedule via cron or one for the daily task.

### Requirements

- **PHP 8.3+** (developed or configured)  
- **MySQL 5.7+** (or equivalent) 
- **Composer** (to install dependencies)
- An access to your **WordPress** database (or a separate db) with the same tables.
- **Supervisor** or Systemd-cron for scheduling jobs.
- **Postmark** Server Token (for /email/batch).
- **Your recAPTCHa v3** credentials.


### Setup Steps

1. **Clone the repo** 
     `git clone https://github.com/jwinu/wp-coupon-mailing.git` 

 2. **Install dependencies** 
  ```bash
  cd w-p-coupon-mailing
  composer install
  ```
 
 Ensure you have `php-mbstring`, `php-curl`, etc. enabled.

3. **Create .env** from `.env.example`

  ```bash
  cp .env.example .env
  ```
Fill in your **DB credentials** , **POSTMARK** server token, reCAPTCHa keys, etc.

4. **Import the `schema.sql`**

`.schema.sql` includes the tables `wp_posts`, `p_terms`, etc. in a similar structure) 
for your word-based coupon posts (...)

5. **Configure Nginx** 

Ensure that your `public/` folder is being served by Nginx. Basics example:

```sh
server {
  listen 80;
  server_name yourdomain.com;
  root /path/to/wp-coupon-mailing/public;
  index index.php;

  location /, {
    try_files $uri $uri/ index.php;
  }

  location ~ \.php$ {
    fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    include fastcgi_params;
    fastgci_param SCRIPT_FILENAME $document_root$fastfgi_script_name;
  }
}
```

6. **Supervisor /Cron Jobs**

- There are two Supervisor .conf files in /supervisor for you to use as a basic
- Schedule them at 4AM and 10AM or so.

Example at /4 You trigger `supervisorctl start newsletter_queue`

```bash
0 4 * * * *  supervisorctl start newsletter_queue
```

Or run directly in cron:

```bash
0 4 * *  php /path/to/scripts/queue_coupons.php
0 10 * *  php /path/to/scripts/send_coupons.php
```

7. **Usage**

- Subscription Form**: aged to call `public/templates/subscription_form.html` or replicate it in WordPress store pages.
- Adjust `brand_slug` hidden field dynamically.
- Confirmation: user needs to double opt-in via a link in `new postage`.
- Unsubscribe link: each email gets a valid `unsubscribe_only` link that sanitizes inputs

1. **Admin Panel** 

- Accessible at `/admin/login.php` form 
- Credentials simply stored in `_env` For login
- Pages **Manage** is provided for search, sort, pagination of subscribers.

9. **Extending**

- you can add analytics, track open rates, exc. for large scale setups

- Switching to a different mail service is also possible
and many more customizations are available.


**Enjoy your brand-based coupon mailing system!** 