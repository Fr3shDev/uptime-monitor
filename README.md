# Uptime Monitor

A Laravel 13 API that monitors website uptime, tracks check history, and sends email notifications when sites go down or recover.

---

## Features

- Register URLs to monitor with configurable check intervals and failure thresholds
- Automated background checks via Laravel's job scheduler and queue
- Full check history with pagination
- Email notifications on site down and recovery
- Uptime percentage calculation per monitor

---

## Requirements

| Dependency | Version |
|---|---|
| PHP | >= 8.4 |
| Composer | >= 2.x |
| MySQL | >= 8.0 |
| Laravel Herd (local) | Latest |

---

## Local Setup

### 1. Clone the repository

```bash
git clone https://github.com/Fr3shDev/uptime-monitor.git
cd uptime-monitor
```

### 2. Install dependencies

```bash
composer install
```

### 3. Create your environment file

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configure the database

Open `.env` and update:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=uptime_monitor
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Create the MySQL database

```bash
mysql -u root -e "CREATE DATABASE uptime_monitor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Or open TablePlus, connect to your local MySQL server, and create a database named `uptime_monitor`.

### 6. Run the migrations

```bash
php artisan migrate
```

### 7. Configure notifications

Open `.env` and set the email address that should receive up/down alerts:

```dotenv
UPTIME_NOTIFICATION_EMAIL=you@example.com
```

For local testing, emails are written to the log file instead of actually sent:

```dotenv
MAIL_MAILER=log
```

Check `storage/logs/laravel.log` to see notification output.

To send real emails, configure an SMTP provider (e.g. Mailtrap for testing):

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
```

### 8. Start the queue worker

The queue worker processes the URL check jobs in the background. Open a dedicated terminal and run:

```bash
php artisan queue:work --tries=1
```

Keep this running the entire time you are using the application.

### 9. Start the scheduler

The scheduler dispatches a check job every minute for any monitor that is due. Open a second terminal and run:

```bash
php artisan schedule:work
```

Keep this running alongside the queue worker.

### 10. View the application

If you are using Laravel Herd, the API is available at:

```
http://uptime-monitor.test/api
```

No `php artisan serve` needed with Herd. If you prefer the built-in server:

```bash
php artisan serve
# available at http://localhost:8000/api
```

---

## API Documentation

Full interactive API documentation is available via Scribe.

After setup, generate and open the docs:

```bash
php artisan scribe:generate
```

Then visit:

```
http://uptime-monitor.test/docs
```

See the **API Docs** section below for a quick reference.

---

## Quick API Reference

### Register a monitor

```bash
POST /api/monitors
Content-Type: application/json

{
  "url": "https://example.com",
  "check_interval": 5,
  "threshold": 3
}
```

| Field | Type | Required | Description |
|---|---|---|---|
| `url` | string | Yes | A valid unique HTTP/HTTPS URL |
| `check_interval` | integer | No | Minutes between checks (default: 5, min: 1, max: 60) |
| `threshold` | integer | No | Consecutive failures before marking as down (default: 3, min: 1) |

### List all monitors

```bash
GET /api/monitors
```

### Get check history

```bash
GET /api/monitors/{id}/history?page=1&per_page=15
```

| Param | Type | Required | Description |
|---|---|---|---|
| `page` | integer | No | Page number (default: 1) |
| `per_page` | integer | No | Results per page (default: 15, max: 100) |

---

## How It Works

1. You register a URL via `POST /api/monitors`
2. Every minute the scheduler checks which monitors are due for their next ping based on `check_interval`
3. A `CheckMonitorJob` is dispatched to the queue for each due monitor
4. The job hits the URL, records the result in `monitor_checks`, and updates the monitor's status
5. If a monitor accumulates `threshold` consecutive failures, its status changes to `down` and a notification email is sent
6. When it responds successfully again, the status changes back to `up` and a recovery email is sent

---

## Architecture

```
app/
├── Contracts/
│   └── MonitorRepositoryInterface.php   # Interface defining the data contract
├── Http/
│   ├── Controllers/Api/
│   │   ├── MonitorController.php         # POST /api/monitors, GET /api/monitors
│   │   └── MonitorHistoryController.php  # GET /api/monitors/{id}/history
│   ├── Requests/
│   │   └── StoreMonitorRequest.php       # Validation
│   └── Resources/
│       ├── MonitorResource.php           # Monitor JSON shape
│       └── MonitorCheckResource.php      # Check history JSON shape
├── Jobs/
│   └── CheckMonitorJob.php              # Pings a URL, records result, sends mail
├── Mail/
│   ├── MonitorDownMail.php
│   └── MonitorUpMail.php
├── Models/
│   ├── Monitor.php
│   └── MonitorCheck.php
├── Providers/
│   └── AppServiceProvider.php           # Binds interface to repository
└── Repositories/
    └── MonitorRepository.php            # Eloquent implementation
```

### Key design decisions

**Repository pattern** — Controllers depend on `MonitorRepositoryInterface`, not Eloquent directly. This decouples the HTTP layer from the database and makes the code easy to test with a fake implementation.

**`withCount` in the repository** — Uptime percentage requires knowing the total check count and up check count per monitor. Rather than firing two queries per monitor inside the model (N+1), the repository loads both counts in a single SQL query using `withCount`, then the model method reads the pre-loaded values.

**Threshold logic** — `consecutive_failures` increments on each failed check and resets to zero on any success. The monitor only transitions to `down` when the count reaches `threshold`, preventing false alarms from single blips.

**`tries=1` on the job** — A connection timeout or DNS failure is a legitimate data point we want to record, not an error to silently retry. The job catches all exceptions internally and records them as a failed check.

**`MAIL_MAILER=log` locally** — No SMTP server required during development. All emails are written to `storage/logs/laravel.log` and are fully inspectable.

---

## Production Deployment

### 1. Server requirements

- Ubuntu 22.04 / 24.04
- PHP 8.4 with extensions: `mbstring`, `xml`, `curl`, `zip`, `mysql`, `bcmath`
- Composer
- MySQL 8.0+
- Nginx

### 2. Install PHP 8.4

```bash
sudo add-apt-repository ppa:ondrej/php -y
sudo apt-get update
sudo apt-get install -y php8.4 php8.4-fpm php8.4-mbstring php8.4-xml \
    php8.4-curl php8.4-zip php8.4-mysql php8.4-bcmath
```

### 3. Nginx config

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/uptime-monitor/public;

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 4. Deploy

```bash
cd /var/www
git clone https://github.com/Fr3shDev/uptime-monitor.git
cd uptime-monitor

composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
php artisan migrate --force

chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 5. Queue worker with Supervisor

Install Supervisor to keep the queue worker running:

```bash
sudo apt-get install -y supervisor
```

Create `/etc/supervisor/conf.d/uptime-monitor-worker.conf`:

```ini
[program:uptime-monitor-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/uptime-monitor/artisan queue:work --tries=1 --sleep=3 --max-jobs=1000
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/uptime-monitor/storage/logs/worker.log
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start uptime-monitor-worker:*
```

### 6. Scheduler cron

Add this cron entry to run the Laravel scheduler every minute:

```bash
sudo crontab -e
```

```
* * * * * cd /var/www/uptime-monitor && php artisan schedule:run >> /dev/null 2>&1
```

### 7. HTTPS

```bash
sudo apt install certbot python3-certbot-nginx -y
sudo certbot --nginx -d yourdomain.com
```

---

## Environment Variables Reference

| Variable | Default | Description |
|---|---|---|
| `APP_ENV` | `production` | `local` or `production` |
| `APP_DEBUG` | `false` | Show detailed errors |
| `APP_URL` | `http://localhost` | Full public URL |
| `DB_DATABASE` | `uptime_monitor` | Database name |
| `DB_USERNAME` | — | Database username |
| `DB_PASSWORD` | — | Database password |
| `QUEUE_CONNECTION` | `database` | Queue driver |
| `MAIL_MAILER` | `log` | `log`, `smtp`, etc. |
| `UPTIME_NOTIFICATION_EMAIL` | — | Address for up/down alerts |
