#!/usr/bin/env bash

set -Eeuo pipefail

PROJECT_DIR="/var/www/gateway.lamaisondescosmetiques.com"
BRANCH="main"

if [ "$#" -ne 1 ]; then
    echo "ERREUR : environnement obligatoire."
    echo
    echo "Utilisation :"
    echo "  ./deploy.sh dev"
    echo "  ./deploy.sh prod"
    exit 1
fi

ENVIRONMENT="$1"

if [ "$ENVIRONMENT" != "dev" ] && [ "$ENVIRONMENT" != "prod" ]; then
    echo "ERREUR : environnement invalide : $ENVIRONMENT"
    echo "Valeurs autorisées : dev ou prod"
    exit 1
fi

if [ "$ENVIRONMENT" = "prod" ]; then
    APP_DEBUG_VALUE=0
else
    APP_DEBUG_VALUE=1
fi

handle_error() {
    local exit_code=$?

    echo
    echo "========================================"
    echo " ÉCHEC DU DÉPLOIEMENT"
    echo " Environnement : $ENVIRONMENT"
    echo " Code d’erreur : $exit_code"
    echo "========================================"

    exit "$exit_code"
}

trap handle_error ERR

echo "========================================"
echo " Déploiement integration-lmdc"
echo " Environnement : $ENVIRONMENT"
echo "========================================"

if [ ! -d "$PROJECT_DIR" ]; then
    echo "ERREUR : dossier du projet introuvable :"
    echo "$PROJECT_DIR"
    exit 1
fi

cd "$PROJECT_DIR"

if [ ! -d ".git" ]; then
    echo "ERREUR : le dossier n’est pas un dépôt Git."
    exit 1
fi

if [ ! -f "composer.json" ] || [ ! -f "bin/console" ]; then
    echo "ERREUR : projet Symfony introuvable."
    exit 1
fi

if [ ! -f ".env.local" ]; then
    echo "ERREUR : fichier .env.local absent."
    exit 1
fi

if [ ! -f "config/sources.php" ]; then
    echo "ERREUR : fichier config/sources.php absent."
    exit 1
fi

echo
echo "1/7 - Récupération du code"

git checkout "$BRANCH"
git pull --ff-only origin "$BRANCH"

echo
echo "2/7 - Installation des dépendances"

if [ "$ENVIRONMENT" = "prod" ]; then
    APP_ENV=prod APP_DEBUG=0 composer install \
        --no-dev \
        --optimize-autoloader \
        --no-interaction
else
    APP_ENV=dev APP_DEBUG=1 composer install \
        --no-interaction
fi

echo
echo "3/7 - Vérification de la configuration Symfony"

APP_ENV="$ENVIRONMENT" APP_DEBUG="$APP_DEBUG_VALUE" \
    php bin/console lint:container

echo
echo "4/7 - Exécution des migrations Doctrine"

APP_ENV="$ENVIRONMENT" APP_DEBUG="$APP_DEBUG_VALUE" \
    php bin/console doctrine:migrations:migrate \
    --no-interaction \
    --allow-no-migration

echo
echo "5/7 - Reconstruction du cache"

APP_ENV="$ENVIRONMENT" APP_DEBUG="$APP_DEBUG_VALUE" \
    php bin/console cache:clear

APP_ENV="$ENVIRONMENT" APP_DEBUG="$APP_DEBUG_VALUE" \
    php bin/console cache:warmup

echo
echo "6/7 - Compilation des assets"

APP_ENV="$ENVIRONMENT" APP_DEBUG="$APP_DEBUG_VALUE" \
    php bin/console asset-map:compile

echo
echo "7/7 - Vérifications finales"

APP_ENV="$ENVIRONMENT" APP_DEBUG="$APP_DEBUG_VALUE" \
    php bin/console about

APP_ENV="$ENVIRONMENT" APP_DEBUG="$APP_DEBUG_VALUE" \
    php bin/console doctrine:schema:validate

echo
echo "========================================"
echo " DÉPLOIEMENT TERMINÉ AVEC SUCCÈS"
echo " Environnement : $ENVIRONMENT"
echo "========================================"
