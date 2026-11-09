<?php

namespace App\Tests\Service;

use App\Service\SourceConfigService;
use PHPUnit\Framework\TestCase;

class SourceConfigServiceTest extends TestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir() . '/gateway-test-' . bin2hex(random_bytes(4));
        mkdir($this->projectDir . '/config', 0777, true);
        file_put_contents($this->sourcesFile(), "<?php\n\nreturn ['sources' => []];\n");
    }

    protected function tearDown(): void
    {
        @unlink($this->sourcesFile());
        @rmdir($this->projectDir . '/config');
        @rmdir($this->projectDir);
    }

    public function testSaveKeepsSourceEnabledWhenConnectionFails(): void
    {
        $service = new SourceConfigService($this->projectDir);

        $error = $service->save('shop', $this->formData());

        self::assertNotNull($error);
        self::assertTrue($service->get('shop')['enabled']);
    }

    public function testSaveWithoutEnabledDoesNotTestConnection(): void
    {
        $service = new SourceConfigService($this->projectDir);

        $data = $this->formData();
        unset($data['enabled']);

        self::assertNull($service->save('shop', $data));
        self::assertFalse($service->get('shop')['enabled']);
    }

    public function testCheckEnabledSourcesDoesNotChangeConfiguration(): void
    {
        $service = new SourceConfigService($this->projectDir);
        $service->save('shop', $this->formData());
        $before = file_get_contents($this->sourcesFile());

        $status = $service->checkEnabledSources();

        self::assertSame(['shop' => false], $status);
        self::assertSame($before, file_get_contents($this->sourcesFile()));
        self::assertTrue($service->get('shop')['enabled']);
    }

    /**
     * Port local fermé : la connexion échoue immédiatement, sans accès réseau.
     *
     * @return array<string, string>
     */
    private function formData(): array
    {
        return [
            'label' => 'Boutique',
            'version' => '8',
            'host' => '127.0.0.1',
            'port' => '1',
            'database' => 'shop',
            'user' => 'user',
            'password' => 'not-a-real-password',
            'prefix' => 'ps_',
            'platform' => 'b2c',
            'enabled' => '1',
        ];
    }

    private function sourcesFile(): string
    {
        return $this->projectDir . '/config/sources.php';
    }
}
