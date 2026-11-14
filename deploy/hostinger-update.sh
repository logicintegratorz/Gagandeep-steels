#!/usr/bin/env bash
# Runs on the Hostinger server. Arguments: <laravel-app-root> <public-html-root>
# This file deliberately never copies or edits the server's .env file.
set -euo pipefail

APP_ROOT=${1:?Laravel application path is required}
PUBLIC_ROOT=${2:?public_html path is required}
PHP_BIN=${PHP_BIN:-php}

cd "$APP_ROOT"

if [[ ! -f .env ]]; then
  echo "ERROR: $APP_ROOT/.env is missing. Deployment stopped without touching the database." >&2
  exit 1
fi

# Hostinger shared PHP disables proc_open. Composer post-autoload scripts would
# fail there, so dependencies are installed first and Laravel discovery is run
# explicitly below.
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader --no-scripts

"$PHP_BIN" artisan package:discover --ansi
"$PHP_BIN" artisan down --retry=60 || true
trap '"$PHP_BIN" artisan up || true' EXIT

"$PHP_BIN" artisan migrate --force
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chmod -R ug+rw storage bootstrap/cache

# The temporary Hostinger domain points at public_html while Laravel is kept in
# crmapp. Copy only public assets and repoint the front controller to crmapp.
mkdir -p "$PUBLIC_ROOT"
cp -a public/. "$PUBLIC_ROOT/"
sed -i "s#'/../vendor/#'/../crmapp/vendor/#; s#'/../bootstrap/#'/../crmapp/bootstrap/#" "$PUBLIC_ROOT/index.php"
"$PHP_BIN" artisan storage:link || true
"$PHP_BIN" artisan optimize:clear
"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan route:cache
"$PHP_BIN" artisan view:cache

"$PHP_BIN" artisan up
trap - EXIT
echo "Hostinger deployment completed successfully."
