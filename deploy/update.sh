#!/usr/bin/env bash
set -Eeuo pipefail
umask 077

app=/var/www/steelsmandi
stage=${1:?Usage: update.sh /var/www/steelsmandi-releases/RELEASE}
stage=$(realpath "$stage")
[[ "$stage" =~ ^/var/www/steelsmandi-releases/[0-9]{8}-[0-9]{6}-[a-f0-9]{8}$ ]] || { echo 'Invalid release directory.' >&2; exit 1; }
test -f "$app/.env"
test -f "$app/compose.yaml"
test -f "$stage/deploy/Dockerfile"
exec 9>/run/steelsmandi-deploy.lock
flock -n 9 || { echo 'Another CRM deployment is running.' >&2; exit 1; }
# Pause only this CRM scheduler while switching images and migrating.
exec 8>/run/steelsmandi-scheduler.lock

cd "$app"
compose=(docker compose -p steelsmandi --project-directory "$app" -f "$app/compose.yaml")
previous=$("${compose[@]}" images -q app)
test -n "$previous"
release=$(basename "$stage")
rollback_image="steelsmandi-app:rollback-$release"
docker image tag "$previous" "$rollback_image"

# Build first, with the running app still available. Keep server Compose and secrets.
mkdir -p "$stage/storage/framework/cache/data" "$stage/storage/framework/sessions" "$stage/storage/framework/views" "$stage/storage/logs" "$stage/bootstrap/cache"
docker build -f "$stage/deploy/Dockerfile" -t "steelsmandi-app:release-$release" "$stage"
flock -w 120 8
"${compose[@]}" exec -T -u www-data app php artisan down --retry=30 < /dev/null
switched=0
recover() {
    status=$?
    trap - ERR
    echo 'Deployment failed; attempting to restore the previous application image.' >&2
    if [ "$switched" = 1 ]; then
        docker image tag "$rollback_image" steelsmandi-app:latest
        "${compose[@]}" up -d --no-deps --no-build --force-recreate app || true
        "${compose[@]}" exec -T -u www-data app php artisan optimize < /dev/null || true
    fi
    "${compose[@]}" exec -T -u www-data app php artisan up < /dev/null || true
    echo 'Database migrations are not automatically reversed. Review the deployment error before retrying.' >&2
    exit "$status"
}
trap recover ERR
bash "$app/deploy/backup.sh" < /dev/null
docker image tag "steelsmandi-app:release-$release" steelsmandi-app:latest
switched=1
"${compose[@]}" up -d --no-deps --no-build --force-recreate app < /dev/null
"${compose[@]}" exec -T -u www-data app php artisan migrate --force < /dev/null
"${compose[@]}" exec -T -u www-data app php artisan optimize < /dev/null
"${compose[@]}" exec -T -u www-data app php artisan up < /dev/null
curl --fail --silent --show-error --retry 10 --retry-connrefused --retry-delay 2 http://127.0.0.1:8082/up > /dev/null
curl --fail --silent --show-error http://127.0.0.1:8082/login > /dev/null
# Retain source for inspection and future rebuilds; do not replace runtime config/data.
tar -xzf "$stage/source.tar.gz" -C "$app"
find "$app/deploy" -maxdepth 1 -type f -name '*.sh' -exec sed -i 's/\r$//' {} +
chmod 700 "$app/deploy/backup.sh"
trap - ERR
echo "CRM updated successfully. Release: $release"
echo "Previous image retained: $rollback_image"
