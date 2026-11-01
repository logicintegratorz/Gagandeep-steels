# PostgreSQL production deployment

This application uses PostgreSQL by default. PHP must have `pdo_pgsql` enabled.

1. Create a UTF-8 PostgreSQL database and a dedicated login/owner.
2. Copy `.env.production.example` to `.env` on the server and enter the real URL, database, mail and provider credentials.
3. Generate the application key once: `php artisan key:generate`.
4. Run `composer install --no-dev --optimize-autoloader`.
5. Run `php artisan migrate --force`.
6. Run `php artisan storage:link`, then `php artisan config:cache`, `php artisan route:cache`, and `php artisan view:cache`.
7. Add one cron entry: `* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1`.
8. Point the web server document root to `/path/to/app/public` and enable HTTPS.

Never run `migrate:fresh` on a live database. Take a PostgreSQL backup before every deployment.
