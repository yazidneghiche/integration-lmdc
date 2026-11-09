<?php

namespace App\Tests\Service;

use App\Service\SourceConnection;
use PHPUnit\Framework\TestCase;

class SourceConnectionTest extends TestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir() . '/gateway-test-' . bin2hex(random_bytes(4));
        mkdir($this->projectDir . '/config', 0777, true);
    }

    protected function tearDown(): void
    {
        @unlink($this->projectDir . '/config/sources.php');
        @rmdir($this->projectDir . '/config');
        @rmdir($this->projectDir);
    }

    public function testMissingConfigurationFileIsReported(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Fichier de configuration introuvable');

        new SourceConnection($this->projectDir);
    }

    public function testInvalidConfigurationIsRejected(): void
    {
        $this->writeSources("<?php\n\nreturn ['shops' => []];\n");

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('La configuration des sources est invalide.');

        new SourceConnection($this->projectDir);
    }

    public function testEnabledSourcesSkipDisabledOnes(): void
    {
        $this->writeSources($this->sourcesFile([
            'active' => $this->source(['enabled' => true]),
            'inactive' => $this->source(['enabled' => false]),
            'implicit' => $this->source(),
        ]));

        $connection = new SourceConnection($this->projectDir);

        self::assertSame(['active', 'implicit'], $connection->getEnabledSources());
    }

    public function testConfigExposesVersionAndPrefix(): void
    {
        $this->writeSources($this->sourcesFile([
            'shop' => $this->source(['version' => '8', 'prefix' => 'ps_']),
        ]));

        $connection = new SourceConnection($this->projectDir);

        self::assertSame('8', $connection->getVersion('shop'));
        self::assertSame('ps_', $connection->getPrefix('shop'));
    }

    public function testUnknownSourceIsRejected(): void
    {
        $this->writeSources($this->sourcesFile([]));

        $this->expectException(\InvalidArgumentException::class);

        (new SourceConnection($this->projectDir))->getConfig('unknown');
    }

    public function testMissingRequiredFieldIsReported(): void
    {
        $source = $this->source();
        unset($source['prefix']);
        $this->writeSources($this->sourcesFile(['shop' => $source]));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Le paramètre "prefix" est absent pour la source "shop".');

        (new SourceConnection($this->projectDir))->getConfig('shop');
    }

    public function testDisabledSourceIsNotConnected(): void
    {
        $this->writeSources($this->sourcesFile([
            'shop' => $this->source(['enabled' => false]),
        ]));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('La source "shop" est désactivée.');

        (new SourceConnection($this->projectDir))->connect('shop');
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function source(array $overrides = []): array
    {
        return $overrides + [
            'host' => '127.0.0.1',
            'database' => 'shop',
            'user' => 'user',
            'password' => '',
            'version' => '8',
            'prefix' => 'ps_',
        ];
    }

    /**
     * @param array<string, array<string, mixed>> $sources
     */
    private function sourcesFile(array $sources): string
    {
        return "<?php\n\nreturn " . var_export(['sources' => $sources], true) . ";\n";
    }

    private function writeSources(string $content): void
    {
        file_put_contents($this->projectDir . '/config/sources.php', $content);
    }
}
