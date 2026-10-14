#!/usr/bin/env bash
set -e

if [[ $# -ne 1 ]] || [[ "$1" != "dev" && "$1" != "prod" ]]; then
    echo "Utilisation : ./deploy.sh dev|prod"
    exit 1
fi

export APP_ENV="$1"

cd /var/www/gateway.lamaisondescosmetiques.com

if [[ "$APP_ENV" == "prod" ]]; then
    export APP_DEBUG=0
else
    export APP_DEBUG=1
fi

git pull --ff-only origin main

if [[ "$APP_ENV" == "prod" ]]; then
    composer install \
        --no-dev \
        --optimize-autoloader \
        --no-interaction \
        --no-scripts
else
    composer install --no-interaction --no-scripts
fi

php bin/console doctrine:schema:update --force

composer run-script auto-scripts --no-interaction

if [[ "$APP_ENV" == "prod" ]]; then
    php bin/console asset-map:compile
fi

php bin/console cache:clear
php bin/console cache:warmup

echo "Déploiement intégration terminé : $APP_ENV."
