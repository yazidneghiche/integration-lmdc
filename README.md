# Integration Gateway LMDC

Cette application synchronise les comptes Sage, les clients PrestaShop et les commandes avant leur envoi vers l'API LMDC.

## Installation

```bash
composer install
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate
```

Configurer dans `.env.local` :

```dotenv
DATABASE_URL="mysql://user:password@127.0.0.1:3306/integration-gateway?serverVersion=8.0.37&charset=utf8mb4"
API_BASE_URL="http://adresse-api"
API_KEY="cle-api"
API_MONITORING_PATH="/api/monitoring"
MAILER_DSN="smtp://user:password@serveur:587"
MAIL_FROM="integration@lmdc-lab.com"
MAIL_TO="destinataire@lmdc-lab.com"
ADMIN_USER="admin"
ADMIN_PASSWORD="mot-de-passe-solide"
```

Lors d'une nouvelle installation, copier également `config/sources.php.dist` vers `config/sources.php`, puis renseigner la première source depuis l'administration.

## Administration

L'interface est disponible sur `/admin`. Elle permet de consulter le monitoring et de gérer les sources PrestaShop.

Le serveur PHP doit avoir le droit d'écrire dans `config/sources.php` pour utiliser le formulaire.

## Commandes

Synchronisation complète des comptes Sage et des sources PrestaShop :

```bash
php bin/console app:sync:gateway
```

Synchronisation d'une seule source :

```bash
php bin/console app:sync:gateway anyskin
```

Envoi des nouveaux comptes vers l'API :

```bash
php bin/console app:sync:accounts:push
```

Envoi des commandes disposant d'un CT Number :

```bash
php bin/console app:sync:orders:push
```

## Ordre recommandé pour le cron

```cron
*/15 * * * * cd /chemin/integration-gateway-lmdc && php bin/console app:sync:gateway >> var/log/cron.log 2>&1
5-59/15 * * * * cd /chemin/integration-gateway-lmdc && php bin/console app:sync:accounts:push >> var/log/cron.log 2>&1
10-59/15 * * * * cd /chemin/integration-gateway-lmdc && php bin/console app:sync:orders:push >> var/log/cron.log 2>&1
```

## Contrôles avant utilisation

```bash
php bin/console lint:container
php bin/console lint:twig templates
php bin/console doctrine:schema:validate
php bin/console debug:router
php bin/phpunit
```
