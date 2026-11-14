# Gagandeep Steels CRM — Laravel + MySQL

Production CRM for leads, pipeline, tasks, reminders, activities, products, calling, notifications, content documents and administrator-managed navigation.

## Requirements

- PHP 8.3+ with PDO and `pdo_mysql`
- MySQL 8+ or MariaDB 10.6+
- Composer 2
- A web server whose document root points only to `public/`

## Local setup

1. Create MySQL databases and a dedicated application user:

   ```sql
   CREATE DATABASE gagandeep_steels CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE DATABASE gagandeep_steels_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. Copy `.env.example` to `.env`, set the MySQL credentials and run:

   ```powershell
   composer install
   php artisan key:generate
   php artisan migrate --force
   php artisan storage:link
   ```

3. Start the CRM with `start-local.cmd`. Local MySQL must be listening on `127.0.0.1:3307`.

## Tests

Copy `.env.testing.example` to `.env.testing`, point it to a disposable database whose name ends in `_test`, then run:

```powershell
php artisan test
```

Tests recreate only the protected `_test` database. Never point test settings at production.

## Production

Use `.env.production.example` and follow `HOSTINGER-MYSQL-DEPLOYMENT.md`. Keep `APP_DEBUG=false`, HTTPS and secure cookies enabled. Add the Laravel scheduler cron entry so imports, reminders, list automations, tenure processing and same-stage lead alerts run automatically.

Never run `migrate:fresh` against a live database. Back up MySQL before every deployment.
