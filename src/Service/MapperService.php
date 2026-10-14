<?php

namespace App\Service;

use App\Entity\Account;
use App\Entity\Order;
use App\Entity\OrderProduct;
use App\Repository\AccountRepository;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Yaml\Yaml;

class MapperService
{
    private array $sources;

    public function __construct(
        private string $projectDir,
        private readonly EntityManagerInterface $em,
        private readonly AccountRepository $accountRepository,
        private readonly OrderRepository $orderRepository,
        private readonly SourceConnection $sourceConnection,
    ) {
        $config = require $projectDir . '/config/mapping.php';

        if (!is_array($config)) {
            throw new \RuntimeException('Le fichier config/mapping.php est invalide.');
        }

        $this->sources = $config;
    }

    /**
     * Transforme et enregistre les données d'une source.
     *
     * @return array{created: int, updated: int, skipped: int}
     */
    public function run(array $datas, string $source, string $index): array
    {
        $this->sourceConnection->getConfig($source);

        return match ($index) {
            'account' => $this->runDataAccount($datas, $source),
            'order' => $this->runDataOrder($datas, $source),
            default => throw new \InvalidArgumentException(sprintf(
                'Type de données "%s" inconnu.',
                $index
            )),
        };
    }

    /**
     * @return array{created: int, updated: int, skipped: int}
     */
    private function runDataAccount(array $datas, string $source): array
    {
        $mapping = $this->mappingFor($source)['account'];
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($datas as $data) {
            $email = $this->normalizeEmail($data['email'] ?? null);

            if ($email === null) {
                ++$skipped;
                continue;
            }

            $account = $this->accountRepository->findOneBy(['email' => $email]);

            if ($account === null) {
                $account = (new Account())
                    ->setEmail($email)
                    ->setCtNumber(null)
                    ->setIsNew(true)
                    ->setValidate(false)
                    ->setSync(false);
                $this->em->persist($account);
                ++$created;
            } else {
                ++$updated;
            }

            $this->mapValues($account, $data, $mapping);
            $account->setEmail($email);

            if ($account->getCompany() === null || $account->getCompany() === '') {
                $account->setCompany('Particulier');
            }
        }

        $this->em->flush();

        return compact('created', 'updated', 'skipped');
    }

    /**
     * @return array{created: int, updated: int, skipped: int}
     */
    private function runDataOrder(array $datas, string $source): array
    {
        $mapping = $this->mappingFor($source);
        $platform = (string) ($this->sourceConnection->getConfig($source)['platform'] ?? 'b2c');
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($datas as $data) {
            $orderData = $data['order'] ?? [];
            $itemsData = $data['items'] ?? [];
            $sourceOrderId = isset($orderData['id_order'])
                ? (string) $orderData['id_order']
                : '';
            $email = $this->normalizeEmail($orderData['email'] ?? null);

            if ($sourceOrderId === '' || $email === null) {
                ++$skipped;
                continue;
            }

            $account = $this->accountRepository->findOneBy(['email' => $email]);

            if ($account === null) {
                throw new \RuntimeException(sprintf(
                    'Aucun compte trouvé pour %s dans la source %s.',
                    $email,
                    $source
                ));
            }

            $order = $this->orderRepository->findOneBy([
                'source' => $source,
                'sourceOrderId' => $sourceOrderId,
            ]);

            if ($order === null) {
                $order = new Order();
                $this->em->persist($order);
                ++$created;
            } else {
                foreach ($order->getOrderProducts()->toArray() as $item) {
                    $order->removeOrderProduct($item);
                }
                ++$updated;
            }

            $this->mapValues($order, $orderData, $mapping['order']);

            $order
                ->setEmail($email)
                ->setAccount($account)
                ->setSource($source)
                ->setPlatform($platform)
                ->setSourceOrderId($sourceOrderId)
                ->setCtNumber($account->getCtNumber())
                ->setSync(false);

            foreach ($itemsData as $itemData) {
                $item = new OrderProduct();
                $this->mapValues($item, $itemData, $mapping['orderProduct']);
                $order->addOrderProduct($item);
            }
        }

        $this->em->flush();

        return compact('created', 'updated', 'skipped');
    }

    private function mapValues(object $entity, array $data, array $mapping): void
    {
        foreach ($mapping as $sourceField => $entityField) {
            if (!array_key_exists($sourceField, $data)) {
                continue;
            }

            $setter = 'set' . ucfirst($entityField);

            if (!method_exists($entity, $setter)) {
                continue;
            }

            $entity->{$setter}($this->convertValue($entityField, $data[$sourceField]));
        }
    }

    private function convertValue(string $field, mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return match ($field) {
                'createdAt' => new \DateTimeImmutable(),
                'lastUpdate', 'updatedAt' => null,
                'total', 'totalHt', 'totalTtc', 'price', 'unitPrice' => 0.0,
                'status', 'quantity' => 0,
                'company', 'zipcode', 'city', 'country', 'email',
                'reference', 'name', 'sourceOrderId', 'sourceItemId' => '',
                default => null,
            };
        }

        return match ($field) {
            'createdAt', 'lastUpdate' => $value instanceof \DateTimeImmutable
                ? $value
                : new \DateTimeImmutable((string) $value),
            'updatedAt' => $value instanceof \DateTimeInterface
                ? \DateTime::createFromInterface($value)
                : new \DateTime((string) $value),
            'total', 'totalHt', 'totalTtc', 'price', 'unitPrice' => (float) $value,
            'status', 'quantity', 'sourceCustomerId' => (int) $value,
            'sourceOrderId', 'sourceItemId', 'zipcode' => (string) $value,
            default => is_string($value) ? trim($value) : $value,
        };
    }

    private function normalizeEmail(mixed $email): ?string
    {
        $email = mb_strtolower(trim((string) $email));

        return $email !== '' ? $email : null;
    }

    private function mappingFor(string $source): array
    {
        $mappingFile = sprintf(
            '%s/config/mappings/%s.yaml',
            $this->projectDir,
            $source
        );

        if (is_file($mappingFile)) {
            $mapping = Yaml::parseFile($mappingFile);

            if (!is_array($mapping)) {
                throw new \RuntimeException(sprintf(
                    'Le mapping de la source "%s" est invalide.',
                    $source
                ));
            }

            return $mapping;
        }

        return $this->sources[$source]
            ?? $this->sources['prestashop'];
    }

    private function mappingForOld(string $source): array
    {
        return $this->sources[$source] ?? $this->sources['prestashop'];
    }
}
