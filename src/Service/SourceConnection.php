<?php
namespace App\Service;

use PDO;
use PDOException;

class SourceConnection
{
    private array $sources;

    public function __construct(
        string $projectDir
    )
    {
        $configFile = $projectDir . '/config/sources.php';

        if (!is_file($configFile)) {
            throw new \RuntimeException(
                sprintf(
                    'Fichier de configuration introuvable : %s',
                    $configFile
                )
            );
        }

        $config = require $configFile;

        if (
            !is_array($config)
            || !isset($config['sources'])
            || !is_array($config['sources'])
        ) {
            throw new \RuntimeException(
                'La configuration des sources est invalide.'
            );
        }

        $this->sources = $config['sources'];
    }

    public function connect(string $source): PDO
    {
        $config = $this->getConfig($source);
        $dbname =  $config['database'];
      //  if(!empty($config['prefix']))   $dbname =   $config['prefix'] . $config['database'];

        if (($config['enabled'] ?? true) !== true) {
            throw new \RuntimeException(
                sprintf('La source "%s" est désactivée.', $source)
            );
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            (int)($config['port'] ?? 3306),
            $dbname,
            $config['charset'] ?? 'utf8mb4'
        );

        try {
            return new PDO(
                $dsn,
                $config['user'],
                $config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            throw new \RuntimeException(
                sprintf(
                    'Impossible de se connecter à la source "%s" : %s',
                    $source,
                    $e->getMessage()
                ),
                previous: $e
            );
        }
    }

    public function getConfig(string $source): array
    {
        if (!isset($this->sources[$source])) {
            throw new \InvalidArgumentException(
                sprintf(
                    'La source "%s" n’existe pas dans config/sources.php.',
                    $source
                )
            );
        }

        $config = $this->sources[$source];

        $requiredFields = [
            'host',
            'database',
            'user',
            'password',
            'version',
            'prefix',
        ];


        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $config)) {
                throw new \RuntimeException(
                    sprintf(
                        'Le paramètre "%s" est absent pour la source "%s".',
                        $field,
                        $source
                    )
                );
            }
        }

        return $config;
    }

    public function getVersion(string $source): string
    {
        return (string)$this->getConfig($source)['version'];
    }

    public function getPrefix(string $source): string
    {
        return (string)$this->getConfig($source)['prefix'];
    }

    public function getEnabledSources(): array
    {
        return array_keys(
            array_filter(
                $this->sources,
                static fn(array $config): bool => ($config['enabled'] ?? true) === true
            )
        );
    }
}
