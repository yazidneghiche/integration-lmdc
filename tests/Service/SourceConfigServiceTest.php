<?php

namespace App\Tests\Service;

use App\Service\SourceConfigService;
use PHPUnit\Framework\TestCase;

final class SourceConfigServiceTest extends TestCase
{
    public function testSaveEnregistreUneSource(): void
    {
        $dossier = sys_get_temp_dir() . '/lmdc-' . bin2hex(random_bytes(8));
        $fichier = $dossier . '/config/sources.php';

        mkdir($dossier . '/config', 0700, true);
        file_put_contents($fichier, '<?php return ["sources" => []];');

        try {
            $service = new SourceConfigService($dossier);

            $service->save('fake_shop', ['database' => 'fake_database']);

            self::assertSame('fake_database', $service->get('fake_shop')['database'] ?? null);
            self::assertSame('b2c', $service->get('fake_shop')['platform'] ?? null);
        } finally {
            unlink($fichier);
            rmdir($dossier . '/config');
            rmdir($dossier);
        }
    }
}
