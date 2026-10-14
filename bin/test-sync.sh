#!/usr/bin/env bash

set -euo pipefail

SOURCE="${1:-prestashop}"
SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

cd "$PROJECT_DIR"

echo "1/3 - Création du client et de la commande de test (${SOURCE})"
php bin/console app:test:sync-scenario "$SOURCE"

echo
echo "Le compte a été envoyé à l'API."
echo "Remplace maintenant son CT_Num temporaire par un CT_Num valide dans l'API."
read -r -p "Appuie sur Entrée lorsque le CT_Num est modifié... "

echo
echo "2/3 - Synchronisation des comptes et des données PrestaShop"
php bin/console app:sync:gateway "$SOURCE"

echo
echo "3/3 - Mise à jour des CT_Num et envoi des commandes prêtes"
php bin/console app:sync:orders:push

echo
echo "Test terminé. Consulte également var/log/test-sync/ et les journaux cron."
