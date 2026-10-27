# Meash Cleaning Solution (ሜሽ የፅዳት አገልግሎት)

Meash Cleaning Solution is an enterprise-grade business operating platform and customer booking ecosystem tailored for on-demand residential and commercial cleaning services. Built with Laravel 12, Tailwind CSS, modern Progressive Web App (PWA) architecture, and Telegram Mini Apps.

---

## 🚀 Key Capabilities

1. **Customer Public Portal (`/`)**
   - Interactive multi-step booking wizard with dynamic price calculation.
   - Bilingual support: Amharic (አማርኛ) and English with instant toggle.
   - Self-hosted offline-ready assets (Tailwind CSS, Lucide icons, fonts, Leaflet maps).
   - Ethiopian Calendar (ባህረ ሃሳብ) date conversions.

2. **Internal Business PWA (`/app`)**
   - **Owner / Executive BI**: Real-time revenue, gross vs. net profit, team utilization, expense tracking, and proforma generator.
   - **Reception & Dispatch**: Call intake, automated job assignment, customer timeline, and SMS dispatch.
   - **Cleaners / Mobile Teams**: Today's jobs, live GPS journey tracker, job completion checklists, and offline action queuing.
   - **Sales CRM**: Outdoor sales leads, proforma invoices, contract pipeline, and field visit logs.
   - **100% Offline Support**: IndexedDB caching with Service Worker (`meash-pwa-v3`) and background sync engine.

3. **Telegram Bot & Mini App (`/telegram-miniapp`)**
   - Official Telegram Bot (`@meash_cleaning_solution_bot`) with interactive chat wizard.
   - Telegram WebApp (TMA) embedded booking interface.
   - Real-time order confirmation and customer dispatch notifications.

---

## 🛠️ Tech Stack & Architecture

- **Backend**: Laravel 12, PHP 8.2+, Laravel Sanctum (Token Auth), MySQL (`meash_db`).
- **Testing**: PHPUnit 11 with SQLite `:memory:` isolated test database.
- **Frontend**: Blade Views, Vanilla JS PWA with Service Worker + IndexedDB, Tailwind CSS v3 CLI (self-hosted).
- **Vendor Libraries (Self-Hosted)**: Lucide Icons, Chart.js, Leaflet 1.9.4.
- **Integrations**: Telegram Bot API, AfroMessage SMS Gateway, Ethiopian Calendar Engine.

---

## 💻 Quick Start & Local Setup (Windows / XAMPP)

### Prerequisites
- PHP 8.2 or higher with `pdo_mysql`, `pdo_sqlite`, `curl`, `mbstring` extensions enabled.
- MySQL Server (via XAMPP or standalone).
- Composer (`composer --version`).
- Node.js & NPM (`node -v`).

### 1. Clone & Install Dependencies
```bash
git clone https://github.com/shetesfa/meash-cleaning.git
cd meash-cleaning

# Install PHP dependencies
composer install

# Install JS dependencies & compile CSS
npm install
npm run build:css
```

### 2. Environment Configuration
Copy `.env.example` to `.env`:
```bash
copy .env.example .env
php artisan key:generate
```

### 3. Database Setup & Migration
Ensure MySQL service is started (e.g., in XAMPP Control Panel), then run:
```bash
# Create database meash_db if not exists, then migrate
mysql -u root -e "CREATE DATABASE IF NOT EXISTS meash_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate --force
```

### 4. One-Click Launch Scripts
Windows batch scripts are provided in the project root:
- **`Start_All.bat`**: Launches both the central backend server (port 8080) and the Telegram Bot background polling engine.
- **`Start_Meash_Server.bat`**: Starts the central Laravel server on `http://localhost:8080` with automatic `/health` check.
- **`Start_Without_Bot.bat`**: Starts local offline server on port 8080 without Telegram integration.
- **`Start_Meash_Apache.bat`**: Runs Meash on Apache web server (XAMPP).

---

## 🐳 Docker Setup

To run Meash inside containerized environment:

```bash
# Build and run containers
docker compose up -d --build

# Run migrations
docker compose exec app php artisan migrate --force
```

---

## ⚙️ Environment Variables Reference (`.env`)

| Variable | Description | Example / Recommended |
| :--- | :--- | :--- |
| `APP_NAME` | Name of the platform | `"Meash Cleaning Solution"` |
| `APP_ENV` | Application environment | `production` or `local` |
| `APP_KEY` | Laravel encryption key | `base64:...` (generate via `php artisan key:generate`) |
| `APP_DEBUG` | Detailed error display | `false` in production, `true` in local |
| `APP_URL` | Public base URL | `http://localhost:8080` or `https://meash.et` |
| `DB_CONNECTION` | Primary database driver | `mysql` |
| `DB_HOST` | Database host | `127.0.0.1` |
| `DB_PORT` | Database port | `3306` |
| `DB_DATABASE` | Database name | `meash_db` |
| `DB_USERNAME` | Database username | `root` |
| `DB_PASSWORD` | Database password | `""` or your strong password |
| `TELEGRAM_BOT_TOKEN` | Bot API token from @BotFather | Set your secret bot token |
| `TELEGRAM_BOT_USERNAME` | Bot handle without @ | `meash_cleaning_solution_bot` |
| `TELEGRAM_WEBHOOK_SECRET` | Secret token to secure webhooks | Random 32+ character string |
| `TELEGRAM_MINIAPP_URL` | URL of the Telegram Mini App | `https://shetesfa.github.io/meash-mini-app/` |
| `SMS_PROVIDER` | Active SMS provider | `afromessage` or `gateway` |
| `AFROMESSAGE_TOKEN` | API token from AfroMessage | Bearer token for Ethiopian carrier delivery |
| `SMS_GATEWAY_URL` | Fallback custom SMS HTTP URL | `https://your-sms-gateway.com/send` |
| `SMS_SENDER_ID` | Approved SMS alphanumeric sender ID | `MESH` |
| `MEASH_OWNER_EMAIL` | Initial owner account email | `owner@meash.com` |
| `MEASH_OWNER_PASSWORD` | Initial owner account password | Strong password for first setup |
| `MEASH_OWNER_PHONE` | Primary official business phone | `0911000001` / `0970075509` |

---

## 🔒 Security Best Practices

1. **Telegram Webhook Security**:
   The webhook endpoint `/api/telegram/webhook` strictly enforces `TELEGRAM_WEBHOOK_SECRET`. All requests missing or supplying an invalid `X-Telegram-Bot-Api-Secret-Token` header are immediately rejected with HTTP 401.

2. **Role-Based Access Control**:
   - Cleaners can only query and modify jobs assigned to their own cleaning team.
   - Cleaners cannot access customer personal records or administrative CRM data.
   - Authentication is strictly constrained to registered email or phone with brute-force rate-limiting (5 attempts/min per IP/login).

3. **Zero Secrets in Code**:
   Tokens, database credentials, and API secrets are never hardcoded. Keep `.env` out of version control.

---

## 💾 Database Backup & Restore

### Taking a Backup:
```bash
# Using mysqldump (Windows XAMPP)
C:\xampp\mysql\bin\mysqldump.exe -u root -p meash_db > "meash_backup_%date:~-4,4%%date:~-10,2%%date:~-7,2%.sql"
```

### Restoring a Backup:
```bash
C:\xampp\mysql\bin\mysql.exe -u root -p meash_db < "your_backup_file.sql"
```

---

## 🧪 Running Tests Safely

All automated tests run inside an isolated SQLite `:memory:` database as configured in `phpunit.xml`. **Tests will never touch or erase `meash_db`**.

```bash
# Run the entire test suite (25 tests)
php vendor/phpunit/phpunit/phpunit

# Or via artisan
php artisan test
```

### Test Coverage includes:
- **AuthTest**: Login via email, phone, password rejection, brute-force throttle.
- **AuthorizationTest**: Cleaner multi-tenant isolation, customer list protection.
- **TelegramWebhookTest**: Secret token validation, unauthorized rejection.
- **RouteIntegrityTest**: Verifies 100% of API routes point to valid controller methods.
- **SyncTest**: Offline batch sync error handling and idempotency.
- **MeashOrderTest**, **MeashFinanceTest**, **MeashTelegramTest**, **RealDataEndToEndTest**: End-to-end business workflows.

---

## 📄 License
Proprietary software developed for Meash Cleaning Solution. All rights reserved.
