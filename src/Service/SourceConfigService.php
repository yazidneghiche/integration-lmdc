<?php

namespace App\Service;

use PDO;

class SourceConfigService
{
    private string $configFile;

    public function __construct(string $projectDir)
    {
        $this->configFile = $projectDir . '/config/sources.php';
    }

    public function all(): array
    {
        $config = require $this->configFile;

        return $config['sources'] ?? [];
    }

    public function get(string $name): ?array
    {
        return $this->all()[$name] ?? null;
    }

    public function save(string $name, array $data, ?string $originalName = null): void
    {
        $name = strtolower(trim($name));

        if (!preg_match('/^[a-z0-9_\-]+$/', $name)) {
            throw new \InvalidArgumentException('Le code source contient des caractères invalides.');
        }

        $sources = $this->all();
        $existing = $originalName !== null ? ($sources[$originalName] ?? []) : [];

        if ($originalName === null && isset($sources[$name])) {
            throw new \InvalidArgumentException('Cette source existe déjà.');
        }

        if ($originalName !== null && $originalName !== $name) {
            unset($sources[$originalName]);
        }

        $password = trim((string) ($data['password'] ?? ''));

        $database = trim((string) ($data['database'] ?? ''));
        $prefix = trim((string) ($data['prefix'] ?? 'ps_'));

        if (!preg_match('/^[A-Za-z0-9_\-]+$/', $database)) {
            throw new \InvalidArgumentException('Le nom de la base est invalide.');
        }

        if (!preg_match('/^[A-Za-z0-9_]*$/', $prefix)) {
            throw new \InvalidArgumentException('Le préfixe des tables est invalide.');
        }

        $sources[$name] = [
            'label' => trim((string) ($data['label'] ?? $name)),
            'version' => trim((string) ($data['version'] ?? '8')),
            'driver' => 'pdo_mysql',
            'host' => trim((string) ($data['host'] ?? '127.0.0.1')),
            'port' => (int) ($data['port'] ?? 3306),
            'database' => $database,
            'user' => trim((string) ($data['user'] ?? 'root')),
            'password' => $password !== '' ? $password : ($existing['password'] ?? ''),
            'charset' => 'utf8mb4',
            'prefix' => $prefix,
            'platform' => in_array(($data['platform'] ?? 'b2c'), ['b2b', 'b2c'], true)
                ? $data['platform']
                : 'b2c',
            'enabled' => isset($data['enabled']),
        ];

        ksort($sources);
        $this->write($sources);
    }

    public function remove(string $name): void
    {
        $sources = $this->all();
        unset($sources[$name]);
        $this->write($sources);
    }

    public function test(string $name): void
    {
        $config = $this->get($name);

        if ($config === null) {
            throw new \InvalidArgumentException('Source inconnue.');
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            (int) $config['port'],
            $config['database'],
            $config['charset'] ?? 'utf8mb4'
        );

        new PDO($dsn, $config['user'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }

    private function write(array $sources): void
    {
        $content = "<?php\n\nreturn " . var_export(['sources' => $sources], true) . ";\n";
        $temporaryFile = $this->configFile . '.tmp';

        if (file_put_contents($temporaryFile, $content, LOCK_EX) === false) {
            throw new \RuntimeException('Impossible d’écrire la configuration des sources.');
        }

        if (!rename($temporaryFile, $this->configFile)) {
            @unlink($temporaryFile);
            throw new \RuntimeException('Impossible de remplacer la configuration des sources.');
        }
    }
}
