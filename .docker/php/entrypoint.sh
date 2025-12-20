#!/usr/bin/env bash
set -ex

APP_DIRECTORY=/app

mkdir -p ${APP_DIRECTORY}/var/cache ${APP_DIRECTORY}/var/log

php ${APP_DIRECTORY}/bin/console cache:warmup
php ${APP_DIRECTORY}/bin/console secrets:decrypt-to-local --force

find ${APP_DIRECTORY}/var -exec chown www-data: {} +

exec "$@"
