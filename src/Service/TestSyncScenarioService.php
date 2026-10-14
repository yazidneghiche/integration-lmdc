<?php

namespace App\Service;

use App\Entity\Order;
use App\Repository\AccountRepository;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use PDO;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class TestSyncScenarioService
{
    private array $config;

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        string $projectDir,
        private readonly SourceConnection $sourceConnection,
        private readonly SourceCustomerReader $customerReader,
        private readonly SourceOrderReader $orderReader,
        private readonly MapperService $mapperService,
        private readonly SyncAccountService $syncAccountService,
        private readonly PushNewAccountsService $pushNewAccountsService,
        private readonly UpdateOrderCtNumberService $updateCtNumberService,
        private readonly ApiOrder $apiOrder,
        private readonly AccountRepository $accountRepository,
        private readonly OrderRepository $orderRepository,
        private readonly EntityManagerInterface $em,
    ) {
        $this->config = require $projectDir . '/config/test_sync.php';
        $this->logDirectory = $projectDir . '/var/log/test-sync';
    }

    private string $logDirectory;

    /**
     * Crée une commande dans PrestaShop puis l'importe dans l'intégration.
     */
    public function start(string $source): array
    {
        $this->assertAllowed($source);
        $pdo = $this->sourceConnection->connect($source);
        $prefix = $this->sourceConnection->getPrefix($source);

        // Les comptes Sage doivent être à jour avant l'import PrestaShop.
        $sageSync = $this->syncAccountService->synchronize();
        $customer = $this->nextCustomer($pdo, $prefix);
        $seed = $this->createPrestashopData($pdo, $prefix, $customer);
        $since = new \DateTimeImmutable('-2 minutes');

        $accountResult = $this->mapperService->run(
            $this->customerReader->readCustomers($source, $since),
            $source,
            'account'
        );

        $orders = array_values(array_filter(
            $this->orderReader->readOrdersWithItems($source, $since),
            static fn (array $row): bool => (int) ($row['order']['id_order'] ?? 0) === $seed['order_id']
        ));
        $orderResult = $this->mapperService->run($orders, $source, 'order');

        $account = $this->accountRepository->findOneBy(['email' => $customer['email']]);
        if ($account === null) {
            throw new \RuntimeException('Le compte de test n’a pas été importé.');
        }

        // Envoie uniquement le compte créé par ce scénario.
        $accountPush = $this->pushNewAccountsService->push([$account]);

        $order = $this->orderRepository->findOneBy([
            'source' => $source,
            'sourceOrderId' => (string) $seed['order_id'],
        ]);

        if (!$order instanceof Order) {
            throw new \RuntimeException('La commande de test n’a pas été importée.');
        }

        $result = [
            'step' => 'start',
            'source' => $source,
            'email' => $customer['email'],
            'prestashop_customer_id' => $seed['customer_id'],
            'prestashop_order_id' => $seed['order_id'],
            'integration_order_id' => $order->getId(),
            'ct_number' => $order->getCtNumber(),
            'blocked' => !$this->hasCtNumber($order),
            'sage_sync' => $sageSync,
            'account_import' => $accountResult,
            'order_import' => $orderResult,
            'account_push' => $accountPush,
        ];

        $this->log($result);

        return $result;
    }

    /**
     * Relance la synchro Sage et envoie la commande seulement avec --push.
     */
    public function resume(int $orderId, bool $push = false): array
    {
        $order = $this->orderRepository->find($orderId);

        if (!$order instanceof Order) {
            throw new \RuntimeException(sprintf('Commande %d introuvable.', $orderId));
        }

        $this->assertAllowed($order->getSource() ?? '');
        $sageSync = $this->syncAccountService->synchronize();
        $updated = $this->updateCtNumberService->update();
        $this->em->refresh($order);

        $sent = false;
        $apiResult = null;

        if ($push && $this->hasCtNumber($order)) {
            $apiResult = $this->apiOrder->postOrders([$order]);
            $sent = $this->responseContainsOrder($apiResult, $orderId);

            if ($sent) {
                $order->setSync(true);
                $this->em->flush();
            }
        }

        $result = [
            'step' => 'resume',
            'integration_order_id' => $orderId,
            'ct_number' => $order->getCtNumber(),
            'ready' => $this->hasCtNumber($order),
            'push_requested' => $push,
            'sent' => $sent,
            'sage_sync' => $sageSync,
            'orders_updated' => $updated,
            'api_result' => $apiResult,
        ];

        $this->log($result);

        return $result;
    }

    private function assertAllowed(string $source): void
    {
        if (($this->config['enabled'] ?? false) !== true) {
            throw new \RuntimeException('Le scénario est désactivé dans config/test_sync.php.');
        }

        if (!in_array($source, $this->config['allowed_sources'] ?? [], true)) {
            throw new \RuntimeException(sprintf('La source "%s" n’est pas autorisée pour les tests.', $source));
        }
    }

    private function nextCustomer(PDO $pdo, string $prefix): array
    {
        $statement = $pdo->prepare(sprintf('SELECT 1 FROM `%scustomer` WHERE LOWER(email) = :email LIMIT 1', $prefix));

        foreach ($this->config['customers'] ?? [] as $customer) {
            $email = strtolower(trim((string) ($customer['email'] ?? '')));
            if ($email === '' || $this->accountRepository->findOneBy(['email' => $email])) {
                continue;
            }

            $statement->execute(['email' => $email]);
            if (!$statement->fetchColumn()) {
                $customer['email'] = $email;
                return $customer;
            }
        }

        throw new \RuntimeException('Aucun client de test disponible dans config/test_sync.php.');
    }

    private function createPrestashopData(PDO $pdo, string $prefix, array $customer): array
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $languageId = (int) ($pdo->query(sprintf('SELECT id_lang FROM `%slang` WHERE active = 1 ORDER BY id_lang LIMIT 1', $prefix))->fetchColumn() ?: 1);
        $countryId = (int) ($pdo->query(sprintf('SELECT id_country FROM `%scountry` WHERE active = 1 ORDER BY id_country LIMIT 1', $prefix))->fetchColumn() ?: 1);
        $shopId = (int) ($pdo->query(sprintf('SELECT id_shop FROM `%sshop` ORDER BY id_shop LIMIT 1', $prefix))->fetchColumn() ?: 1);
        $groupId = (int) ($pdo->query(sprintf('SELECT id_group FROM `%sgroup` ORDER BY id_group LIMIT 1', $prefix))->fetchColumn() ?: 1);
        $currencyId = (int) ($pdo->query(sprintf('SELECT id_currency FROM `%scurrency` WHERE active = 1 ORDER BY id_currency LIMIT 1', $prefix))->fetchColumn() ?: 1);
        $carrierId = (int) ($pdo->query(sprintf('SELECT id_carrier FROM `%scarrier` ORDER BY id_carrier LIMIT 1', $prefix))->fetchColumn() ?: 1);

        $product = $pdo->query(sprintf(
            'SELECT p.id_product, p.reference, p.price, pl.name FROM `%sproduct` p LEFT JOIN `%sproduct_lang` pl ON pl.id_product=p.id_product AND pl.id_lang=%d AND pl.id_shop=%d WHERE p.active=1 ORDER BY p.id_product LIMIT 1',
            $prefix,
            $prefix,
            $languageId,
            $shopId
        ))->fetch();

        if (!$product) {
            throw new \RuntimeException('Aucun produit actif disponible dans la base PrestaShop de test.');
        }

        $pdo->beginTransaction();
        try {
            $customerId = $this->insertRow($pdo, $prefix . 'customer', [
                'id_shop' => $shopId, 'id_shop_group' => 1, 'id_gender' => 0, 'id_default_group' => $groupId,
                'id_lang' => $languageId, 'firstname' => $customer['firstname'], 'lastname' => $customer['lastname'],
                'email' => $customer['email'], 'passwd' => password_hash(bin2hex(random_bytes(12)), PASSWORD_BCRYPT),
                'secure_key' => md5(random_bytes(20)), 'company' => '', 'active' => 1, 'deleted' => 0,
                'date_add' => $now, 'date_upd' => $now,
            ]);
            $addressId = $this->insertRow($pdo, $prefix . 'address', [
                'id_country' => $countryId, 'id_customer' => $customerId, 'alias' => 'Test synchronisation',
                'firstname' => $customer['firstname'], 'lastname' => $customer['lastname'], 'address1' => '10 rue du Test',
                'postcode' => '75001', 'city' => 'Paris', 'phone' => '0102030405', 'active' => 1,
                'deleted' => 0, 'date_add' => $now, 'date_upd' => $now,
            ]);
            $priceHt = max(1.0, (float) $product['price']);
            $priceTtc = round($priceHt * 1.2, 6);
            $orderId = $this->insertRow($pdo, $prefix . 'orders', [
                'id_shop_group' => 1, 'id_shop' => $shopId, 'id_carrier' => $carrierId, 'id_lang' => $languageId,
                'id_customer' => $customerId, 'id_cart' => 0, 'id_currency' => $currencyId,
                'id_address_delivery' => $addressId, 'id_address_invoice' => $addressId, 'current_state' => 1,
                'secure_key' => md5(random_bytes(20)), 'payment' => 'Test', 'module' => 'testsync',
                'conversion_rate' => 1, 'total_paid' => $priceTtc, 'total_paid_real' => $priceTtc,
                'total_paid_tax_incl' => $priceTtc, 'total_paid_tax_excl' => $priceHt,
                'total_products' => $priceHt, 'total_products_wt' => $priceTtc,
                'total_shipping' => 0, 'total_shipping_tax_incl' => 0, 'total_shipping_tax_excl' => 0,
                'reference' => 'T' . strtoupper(substr(bin2hex(random_bytes(5)), 0, 8)),
                'valid' => 1, 'date_add' => $now, 'date_upd' => $now,
            ]);
            $this->insertRow($pdo, $prefix . 'order_detail', [
                'id_order' => $orderId, 'id_shop' => $shopId, 'product_id' => (int) $product['id_product'],
                'product_attribute_id' => 0, 'product_name' => $product['name'] ?: 'Produit test',
                'product_quantity' => 1, 'product_reference' => $product['reference'] ?: 'TEST',
                'product_price' => $priceHt, 'unit_price_tax_excl' => $priceHt, 'unit_price_tax_incl' => $priceTtc,
                'total_price_tax_excl' => $priceHt, 'total_price_tax_incl' => $priceTtc,
            ]);
            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }

        return ['customer_id' => $customerId, 'order_id' => $orderId];
    }

    private function insertRow(PDO $pdo, string $table, array $values): int
    {
        $columns = $pdo->query(sprintf('SHOW COLUMNS FROM `%s`', $table))->fetchAll();
        $data = [];
        foreach ($columns as $column) {
            $name = $column['Field'];
            if (str_contains((string) $column['Extra'], 'auto_increment')) {
                continue;
            }
            if (array_key_exists($name, $values)) {
                $data[$name] = $values[$name];
            } elseif ($column['Null'] === 'NO' && $column['Default'] === null) {
                $type = (string) $column['Type'];
                $data[$name] = preg_match('/int|decimal|float|double|bit/i', $type)
                    ? 0
                    : (preg_match('/date|time/i', $type) ? date('Y-m-d H:i:s') : '');
            }
        }
        $names = array_keys($data);
        $sql = sprintf('INSERT INTO `%s` (`%s`) VALUES (:%s)', $table, implode('`,`', $names), implode(',:', $names));
        $pdo->prepare($sql)->execute($data);
        return (int) $pdo->lastInsertId();
    }

    private function hasCtNumber(Order $order): bool
    {
        return trim((string) $order->getCtNumber()) !== '';
    }

    private function responseContainsOrder(?array $response, int $orderId): bool
    {
        foreach ($response['orders'] ?? [] as $value) {
            if ((int) (is_array($value) ? ($value['id'] ?? 0) : $value) === $orderId) {
                return true;
            }
        }
        return false;
    }

    private function log(array $data): void
    {
        if (!is_dir($this->logDirectory)) {
            mkdir($this->logDirectory, 0775, true);
        }
        file_put_contents(
            $this->logDirectory . '/test-sync-' . date('Y-m-d') . '.log',
            json_encode(['date' => date(DATE_ATOM)] + $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }
}
