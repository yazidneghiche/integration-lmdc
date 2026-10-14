<?php

namespace App\Service;

use Symfony\Component\Yaml\Yaml;

class SourceMappingService
{
    private const TABLES = [
        'order' => 'orders',
        'orderProduct' => 'order_detail',
        'account' => 'customer',
        'address' => 'address',
    ];

    private const ADDRESS_FIELDS = [
        'phone',
        'phone_mobile',
        'address1',
        'address2',
        'vat_number',
        'postcode',
        'city',
        'country',
    ];

    private string $defaultMappingFile;
    private string $mappingDirectory;

    public function __construct(
        string $projectDir,
        private readonly SourceConnection $sourceConnection,
    ) {
        $this->defaultMappingFile = $projectDir . '/config/mapping.php';
        $this->mappingDirectory = $projectDir . '/config/mappings';
    }

    /**
     * @return array<string, array{table: string, columns: array<int, string>}>
     */
    public function schema(string $source): array
    {
        $config = $this->sourceConnection->getConfig($source);
        $pdo = $this->sourceConnection->connect($source);
        $prefix = (string) ($config['prefix'] ?? '');
        $schema = [];

        $statement = $pdo->prepare(
            'SELECT COLUMN_NAME
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = :database
               AND TABLE_NAME = :table
             ORDER BY ORDINAL_POSITION'
        );

        foreach (self::TABLES as $context => $tableName) {
            $table = $prefix . $tableName;
            $statement->execute([
                'database' => $config['database'],
                'table' => $table,
            ]);

            $schema[$context] = [
                'table' => $table,
                'columns' => $statement->fetchAll(\PDO::FETCH_COLUMN),
            ];
        }

        return $schema;
    }

    /**
     * @return array{mapping: array<string, array<string, string>>, custom: bool}
     */
    public function mapping(string $source): array
    {
        $customFile = $this->customFile($source);

        if (is_file($customFile)) {
            $mapping = Yaml::parseFile($customFile);

            if (!is_array($mapping)) {
                throw new \RuntimeException('Le fichier de mapping personnalisé est invalide.');
            }

            return [
                'mapping' => $this->splitAddressMapping($mapping),
                'custom' => true,
            ];
        }

        $mappings = require $this->defaultMappingFile;
        $mapping = $mappings[$source] ?? $mappings['prestashop'] ?? null;

        if (!is_array($mapping)) {
            throw new \RuntimeException('Aucun mapping PrestaShop standard n’est disponible.');
        }

        return [
            'mapping' => $this->splitAddressMapping($mapping),
            'custom' => false,
        ];
    }

    /**
     * @param array<string, mixed> $submittedMapping
     */
    public function save(string $source, array $submittedMapping): void
    {
        $this->sourceConnection->getConfig($source);
        $mapping = [
            'order' => $this->cleanContext($submittedMapping['order'] ?? []),
            'orderProduct' => $this->cleanContext($submittedMapping['orderProduct'] ?? []),
            'account' => array_merge(
                $this->cleanContext($submittedMapping['account'] ?? []),
                $this->cleanContext($submittedMapping['address'] ?? [])
            ),
        ];

        if (count($mapping['account']) !== count(array_unique($mapping['account']))) {
            throw new \InvalidArgumentException(
                'Un champ cible du compte est utilisé plusieurs fois.'
            );
        }

        foreach (['order', 'orderProduct', 'account'] as $context) {
            if ($mapping[$context] === []) {
                throw new \InvalidArgumentException(sprintf(
                    'Le mapping du contexte "%s" ne peut pas être vide.',
                    $context
                ));
            }
        }

        if (!is_dir($this->mappingDirectory)
            && !mkdir($concurrentDirectory = $this->mappingDirectory, 0775, true)
            && !is_dir($concurrentDirectory)
        ) {
            throw new \RuntimeException('Impossible de créer le dossier des mappings.');
        }

        $content = Yaml::dump($mapping, 4, 2);
        $file = $this->customFile($source);
        $temporaryFile = $file . '.tmp';

        if (file_put_contents($temporaryFile, $content, LOCK_EX) === false) {
            throw new \RuntimeException('Impossible d’écrire le mapping personnalisé.');
        }

        if (!rename($temporaryFile, $file)) {
            @unlink($temporaryFile);
            throw new \RuntimeException('Impossible d’enregistrer le mapping personnalisé.');
        }
    }

    /**
     * @param mixed $rows
     * @return array<string, string>
     */
    private function cleanContext(mixed $rows): array
    {
        if (!is_array($rows)) {
            return [];
        }

        $mapping = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $sourceField = trim((string) ($row['sourceField'] ?? ''));
            $targetField = trim((string) ($row['targetField'] ?? ''));

            if ($sourceField === '' || $targetField === '') {
                continue;
            }

            if (!preg_match('/^[A-Za-z0-9_]+$/', $sourceField)
                || !preg_match('/^[A-Za-z0-9_]+$/', $targetField)
            ) {
                throw new \InvalidArgumentException('Un champ du mapping contient des caractères invalides.');
            }

            if (in_array($targetField, $mapping, true)) {
                throw new \InvalidArgumentException(sprintf(
                    'Le champ cible "%s" est utilisé plusieurs fois.',
                    $targetField
                ));
            }

            $mapping[$sourceField] = $targetField;
        }

        return $mapping;
    }

    /**
     * @param array<string, mixed> $mapping
     * @return array<string, array<string, string>>
     */
    private function splitAddressMapping(array $mapping): array
    {
        $account = is_array($mapping['account'] ?? null) ? $mapping['account'] : [];
        $address = [];

        foreach (self::ADDRESS_FIELDS as $field) {
            if (isset($account[$field])) {
                $address[$field] = $account[$field];
                unset($account[$field]);
            }
        }

        return [
            'order' => is_array($mapping['order'] ?? null) ? $mapping['order'] : [],
            'orderProduct' => is_array($mapping['orderProduct'] ?? null)
                ? $mapping['orderProduct']
                : [],
            'account' => $account,
            'address' => $address,
        ];
    }

    private function customFile(string $source): string
    {
        if (!preg_match('/^[a-z0-9_-]+$/', $source)) {
            throw new \InvalidArgumentException('Le code de la source est invalide.');
        }

        return $this->mappingDirectory . '/' . $source . '.yaml';
    }
}
