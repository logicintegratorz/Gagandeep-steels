# Gagandeep Steels CRM — Laravel + PHP + MySQL

For the configured local installation, see [LOCAL-TESTING.md](LOCAL-TESTING.md). Double-click start-local.cmd to launch.

This replaces the earlier TypeScript implementation. The UI is rendered by Laravel Blade; PHP handles validation, queries and writes; MySQL stores the records. Plain CSS and a small JavaScript file provide styling and confirmation dialogs. React, Node.js, npm and a frontend build are not required.

## Install on Windows

1. PHP 8.3 or newer. Enable the Laravel-required extensions, including ctype, curl, dom, fileinfo, filter, hash, mbstring, openssl, pcre, PDO, pdo_mysql, session, tokenizer and xml. PHP 8.3+ and these extensions should be available to the command line.
2. Composer 2 (PHP dependency manager).
3. MySQL 8.0 or newer, running locally.
4. Visual Studio Code.

Verify in a NEW VS Code terminal:

```powershell
php -v
composer --version
mysql --version
```

If a command is not recognized, add that program's installation directory to PATH and restart VS Code. A MySQL GUI can create the database even when the mysql command is not on PATH. Existing npm is not needed for this version.

## Open the project

Extract `hauldesk-laravel-mysql.zip`, then VS Code > File > Open Folder. Select the folder containing `artisan` and `composer.json`.

Alternatively, the all-in-one PHP file contains the entire source. Put it in an empty working folder and run `php hauldesk-laravel-all-in-one.php`; it creates a `hauldesk-laravel` subfolder without overwriting existing files. Then open that subfolder.

## First run

In the VS Code terminal in the project folder:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
```

Create the database in MySQL Workbench, phpMyAdmin or the MySQL client:

```sql
CREATE DATABASE hauldesk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Open `.env` and set YOUR MySQL credentials:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hauldesk
DB_USERNAME=root
DB_PASSWORD="your-local-mysql-password"
```

Use your configured database user; `root` is only a common local example. A blank password works only if your local MySQL account is actually configured that way. Never paste production credentials into shared code.

Run:

```powershell
php artisan config:clear
php artisan migrate
php artisan serve --host=127.0.0.1 --port=8000
```

Open http://127.0.0.1:8000. Keep the terminal running. Stop it with Ctrl+C.

For subsequent launches, start MySQL and run `php artisan serve`. Migrations create the table; the app starts empty. Data remains in MySQL when the PHP server stops. `php artisan migrate` is safe to rerun; do not use `migrate:fresh` on your real database.

## Features

- Add, edit and delete rates: origin, destination, INR price, transporter and remarks.
- MySQL DECIMAL prices, automatic updated dates and version checks to prevent stale updates/deletes.
- Search, city/transporter filters, price sorting and server-side pagination.
- CSV export of ALL matching rows across pages, with CSV escaping and formula protection.
- Laravel validation, CSRF protection, parameterized queries, escaped Blade output and flash feedback.
- Required-field errors preserve typed form values. On a version conflict, reopen the rate from the directory to obtain the new version.
- Responsive layout; delete confirmation requires JavaScript. Other core forms work without JavaScript.

## Files to edit

- `resources/views/rates/index.blade.php`: frontend page.
- `resources/views/components/icon.blade.php`: interface icons.
- `public/css/app.css`: theme and responsive CSS.
- `public/js/app.js`: delete dialog, mobile navigation and submit feedback.
- `app/Http/Controllers/FreightRateController.php`: PHP CRUD, filters and CSV export.
- `app/Http/Requests/FreightRateRequest.php`: input validation.
- `app/Models/FreightRate.php`: Eloquent model and INR formatting.
- `database/migrations/`: MySQL table schema.
- `routes/web.php`: application routes.
- `.env`: your local secrets and database connection; do not commit it.

## Verification status and tests

The local Windows setup has been verified with PHP 8.4.26 and MySQL 8.4.9. All 6 feature tests passed (44 assertions). The branded page and logo returned HTTP 200. Composer dependencies are installed and composer.lock records their versions. See LOCAL-TESTING.md for the configured launcher and local database details.

Feature tests cover save/read, validation, update/delete conflicts, filters, pagination, output escaping and CSV protection. To run them, create a separate disposable database named `hauldesk_test`:

```sql
CREATE DATABASE hauldesk_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```powershell
Copy-Item .env.testing.example .env.testing
php artisan key:generate --env=testing
```

Set the test database credentials in `.env.testing`, then run:

```powershell
php artisan config:clear
composer test
```

Tests refuse databases not ending in `_test` and recreate the tables for each test. Use a disposable database only. Never put a live database in `.env.testing`.

## Scope and deployment

This is the first freight-rate module. It includes user accounts, login, password recovery and role-based permissions. Customers, sales and invoices are not yet included. It is intended for local development; authenticated users access the shared rate book according to their assigned permissions. See AUTH-AND-PERMISSIONS.md for setup and email delivery.

This Laravel/PHP application requires a PHP-capable host with MySQL. It is not compatible with the earlier Cloudflare Worker/Sites deployment. For a real PHP deployment, point the web root ONLY to `public/`, use HTTPS, set `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, use a least-privilege MySQL account and configure backups. Do not expose the project root or `.env`.

Framework reference: https://laravel.com/docs/13.x



