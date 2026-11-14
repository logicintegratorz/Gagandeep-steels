# Hostinger shared hosting deployment (MySQL)

This CRM is configured for MySQL/MariaDB, supported on Hostinger web hosting. Keep hPanel database credentials only in production `.env`.

1. In hPanel, create a MySQL database and user, then grant the user full access.
2. Upload the project outside `public_html` when possible and set the domain document root to the project `public` directory.
3. Copy `.env.production.example` to `.env`. Set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, and the MySQL database host, port, name, user and password supplied by hPanel.
4. In Hostinger SSH terminal run:

   ```bash
   composer install --no-dev --prefer-dist --optimize-autoloader
   php artisan key:generate
   php artisan migrate --force
   php artisan storage:link
   php artisan optimize:clear
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

5. Add an hPanel cron job, replacing the path and PHP binary with your real values:

   ```bash
   * * * * * cd /home/u123456789/domains/example.com/crm && php artisan schedule:run >> /dev/null 2>&1
   ```

Never run `migrate:fresh` in production. Create an hPanel MySQL backup before every release.
