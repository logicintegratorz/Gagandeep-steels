#!/bin/sh
set -eu
cd /var/www/steelsmandi
umask 077
mkdir -p /var/backups/steelsmandi
stamp=$(date -u +%Y%m%dT%H%M%SZ)
destination=/var/backups/steelsmandi/$stamp
mkdir "$destination"
docker compose exec -T db sh -c 'MYSQL_PWD="$MYSQL_PASSWORD" mysqldump -u "$MYSQL_USER" --single-transaction --no-tablespaces "$MYSQL_DATABASE"' < /dev/null > "$destination/database.sql"
docker compose exec -T app tar -czf - -C /var/www/html storage/app < /dev/null > "$destination/uploads.tar.gz"
cp .env "$destination/environment"
gzip "$destination/database.sql"
echo "Backup completed: $destination"
