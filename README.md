# Gagandeep Steels CRM — Laravel + PostgreSQL

Production CRM for leads, pipeline, tasks, reminders, activities, products, calling, notifications, content documents and administrator-managed navigation.

## Requirements

- PHP 8.3+ with PDO and `pdo_pgsql`
- PostgreSQL 15+
- Composer 2
- A web server whose document root points only to `public/`

## Local setup

1. Create PostgreSQL databases owned by a dedicated application role:

   ```sql
   CREATE ROLE gagandeep LOGIN PASSWORD 'strong-password';
   CREATE DATABASE gagandeep_steels OWNER gagandeep ENCODING 'UTF8';
   CREATE DATABASE gagandeep_steels_test OWNER gagandeep ENCODING 'UTF8';
   ```

2. Copy `.env.example` to `.env`, set the PostgreSQL credentials and run:

   ```powershell
   composer install
   php artisan key:generate
   php artisan migrate --force
   php artisan storage:link
   ```

3. Start the CRM with `start-local.cmd`. PostgreSQL must be listening on `127.0.0.1:5432`.

## Tests

Copy `.env.testing.example` to `.env.testing`, point it to a disposable database whose name ends in `_test`, then run:

```powershell
php artisan test
```

Tests recreate only the protected `_test` database. Never point test settings at production.

## Production

Use `.env.production.example` and follow `POSTGRESQL-DEPLOYMENT.md`. Keep `APP_DEBUG=false`, HTTPS and secure cookies enabled. Add the Laravel scheduler cron entry so imports, reminders, list automations, tenure processing and same-stage lead alerts run automatically.

Never run `migrate:fresh` against a live database. Back up PostgreSQL before every deployment.
