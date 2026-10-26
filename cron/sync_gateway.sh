#!/bin/bash

set -e

# Se placer dans le dossier cron
cd "$(dirname "$0")"

# Cron désactivé depuis l'administration
if [ -f "cron.disabled" ]; then
    exit 0
fi

# Racine du projet
cd ..

php bin/console app:sync:gateway
php bin/console app:notify:missing-ct-number
