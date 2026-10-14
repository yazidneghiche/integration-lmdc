# Changements LMDC

## Modifications

- M `.env` : paramètres du monitoring, du mail et de l'administration.
- M `.gitignore` : exclusion du fichier contenant les mots de passe des sources.
- M `assets/styles/app.css` : interface d'administration responsive.
- M `config/mapping.php` : mapping SLA Paris et adresses clients.
- M `config/packages/security.yaml` : protection HTTP de l'administration et des routes gateway.
- M `config/services.yaml` : configuration des nouveaux services et déplacement des logs hors du dossier public.
- M `config/sources.php` : ajout du type de plateforme.
- M `migrations/Version20260916081603.php` : migration initiale utilisable sur une base vide.
- M `src/Command/ImportOrdersCommand.php` : correction de l'appel du mapper et résultat détaillé.
- M `src/Command/PushNewAccountsCommand.php` : notification en cas d'échec d'envoi.
- M `src/Command/PushOrdersCommand.php` : mise à jour des CT Number incluse dans la gestion des erreurs.
- M `src/Controller/Admin/AdminController.php` : tableau de bord et gestion des sources.
- M `src/Controller/GatewayController.php` : routes d'import fonctionnelles sans code de débogage.
- M `src/Entity/Account.php` : ajout de l'adresse et du complément.
- M `src/Entity/Order.php` : ajout de la plateforme et CT Number nullable.
- M `src/Repository/AccountRepository.php` : comptes en attente, rappels et statistiques.
- M `src/Repository/OrderRepository.php` : commandes prêtes et statistiques.
- M `src/Service/ApiOrder.php` : ajout de la source et de la plateforme dans le payload.
- M `src/Service/MapperService.php` : création et mise à jour sans doublonner les commandes.
- M `src/Service/MissingCtNumberService.php` : rappels espacés et destinataires configurables.
- M `src/Service/PushNewAccountsService.php` : envoi collectif, code client temporaire et gestion du résultat.
- M `src/Service/SourceCustomerReader.php` : lecture générique des clients PrestaShop.
- M `src/Service/SourceOrderReader.php` : récupération de l'email pour toutes les sources.
- M `src/Service/SyncAccountService.php` : normalisation des emails, adresses et authentification API.
- M `templates/admin/index.html.twig` : tableau de bord de monitoring.
- M `templates/base.html.twig` : base de l'interface admin.

## Ajouts

- A `migrations/Version20261007120000.php` : colonnes et index complémentaires.
- A `src/Command/ImportAccountsCommand.php` : import manuel des comptes d'une source.
- A `src/Command/SyncGatewayCommand.php` : synchronisation complète de toutes les sources.
- A `src/Service/MonitoringService.php` : appel du endpoint de monitoring.
- A `src/Service/SourceConfigService.php` : ajout, modification, suppression et test des sources.
- A `templates/admin/source_form.html.twig` : formulaire de gestion d'une source.
- A `README.md` : installation, commandes et contrôles.
- A `config/sources.php.dist` : exemple de configuration sans mot de passe.

## Suppressions

- D `src/Service/ApiCustomer.php` : doublon incomplet de `ApiOrder`.
- D `src/Service/MailService.php` : service vide.
- D `src/Service/SaveCustomerAccountService.php` : traitement remplacé par `MapperService`.
- D `public/files/cronlog/sync_order_push.log` : journal retiré du dossier public.
