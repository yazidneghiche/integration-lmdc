#!/bin/bash

# Pas de "set -e" : la notification doit partir même si la synchronisation échoue.

# Se placer dans le dossier cron
cd "$(dirname "$0")" || exit 1

# Cron désactivé depuis l'administration
if [ -f "cron.disabled" ]; then
    exit 0
fi

# Racine du projet
cd .. || exit 1

# Verrou : empêche deux exécutions simultanées
mkdir -p var
exec 9> var/sync_gateway.lock

if ! flock -n 9; then
    echo "[$(date -Is)] Synchronisation déjà en cours, exécution ignorée."
    exit 0
fi

echo "[$(date -Is)] Début de la synchronisation"

php bin/console app:sync:gateway
sync_status=$?

php bin/console app:notify:missing-ct-number
notify_status=$?

echo "[$(date -Is)] Fin de la synchronisation (sync : $sync_status, notification : $notify_status)"

# Code retour : celui de la synchronisation si elle a échoué, sinon celui de la notification
if [ "$sync_status" -ne 0 ]; then
    exit "$sync_status"
fi

exit "$notify_status"
