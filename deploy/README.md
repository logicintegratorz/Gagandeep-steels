# Isolated VPS deployment

Address: http://187.127.159.53:8082

## Delete all CRM data (manual action only)

`powershell -ExecutionPolicy Bypass -File .\delete-data.ps1` only prints a preview.
Add `-Execute` to connect, then type `DELETE-STEELSMANDI-DATA` when prompted.
This separate action backs up first, resets ALL records/users, removes uploaded
files and invalidates sessions, and generates a fresh administrator login in
`/root/steelsmandi-first-login.txt`. Default settings are recreated by migrations.
Server environment, backups and other sites are retained. On failure the CRM
stays in maintenance mode for recovery. Normal code deployment never calls this
reset script. This reset is guarded for the dedicated MySQL `steelsmandi` database.

## Push updated code from Windows

From the project folder, run:

```powershell
powershell -ExecutionPolicy Bypass -File .\push-code.ps1
```

For a specific SSH key or port:

```powershell
.\push-code.ps1 -IdentityFile C:\Users\ihp\.ssh\id_ed25519 -Port 22
```

Use `-PackageOnly` to create the source archive without connecting or deploying.
The script builds the new app image first, then pauses this CRM's scheduler,
enters maintenance mode, backs up its database/uploads/environment, replaces only
the app container, runs migrations and checks `/up` and `/login`. Server `.env`,
Compose configuration, database and storage volumes are preserved. Existing
websites and Nginx are untouched. Brief CRM downtime occurs during the switch.
Failed updates attempt to restore the previous app image; database migrations
are not automatically reversed. Release source and rollback images are retained
on the server under `/var/www/steelsmandi-releases` and in Docker respectively.

The application lives in `/var/www/steelsmandi`. Docker Compose project
`steelsmandi` runs its own PHP 8.4/Apache app and MySQL 8.4 database. Only
port 8082 is published; MySQL has no host port. Existing websites, host Nginx
configuration and domain DNS are not changed.

Server commands:

```sh
cd /var/www/steelsmandi
docker compose ps
docker compose logs --tail=100 app
docker compose exec -u www-data app php artisan migrate --force
docker compose exec -u www-data app php artisan optimize
docker compose exec -u www-data app php artisan crm:recover-admin
```

The generated initial administrator details are stored only on the server in
`/root/steelsmandi-first-login.txt` (root-only). Read over SSH and change the
password in My account after the first login.

The fresh database does not include local/demo records or external provider
credentials. SMTP, WhatsApp, SMS and lead-provider integrations require their
real credentials in CRM settings before delivery can be verified.

The host cron file `/etc/cron.d/steelsmandi` runs the Laravel scheduler each
minute and a database/upload/config backup daily. Backups are root-only in
`/var/backups/steelsmandi`; these copies are on the same server. Copy them to
separate storage for protection against server loss. No automatic deletion
is configured.

The current address uses HTTP. Set up a dedicated subdomain with HTTPS before
using sensitive live data, then update APP_URL and SESSION_SECURE_COOKIE.
Keep the generated APP_KEY and database/storage volumes when updating.
Never run `docker compose down -v` on this installation.
