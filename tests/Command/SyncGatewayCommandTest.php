<?php

namespace App\Tests\Command;

use App\Command\SyncGatewayCommand;
use App\Repository\OrderRepository;
use App\Service\MapperService;
use App\Service\SourceConnection;
use App\Service\SourceCustomerReader;
use App\Service\SourceOrderReader;
use App\Service\SyncAccountService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class SyncGatewayCommandTest extends TestCase
{
    public function testFailingSourceDoesNotStopTheNextOne(): void
    {
        $syncAccountService = $this->createMock(SyncAccountService::class);
        $syncAccountService->method('synchronize')->willReturn(['total' => 3]);

        $sourceConnection = $this->createMock(SourceConnection::class);
        $sourceConnection->method('getEnabledSources')->willReturn(['source_a', 'source_b']);

        $customerReader = $this->createMock(SourceCustomerReader::class);
        $customerReader->method('readCustomers')->willReturnCallback(
            static function (string $source): array {
                if ($source === 'source_a') {
                    throw new \RuntimeException('connexion impossible');
                }

                return [['email' => 'client@example.test']];
            }
        );

        $orderReader = $this->createMock(SourceOrderReader::class);
        $orderReader->expects($this->once())
            ->method('readOrdersWithItems')
            ->with('source_b')
            ->willReturn([]);

        $processed = [];
        $mapperService = $this->createMock(MapperService::class);
        $mapperService->method('run')->willReturnCallback(
            static function (array $datas, string $source, string $index) use (&$processed): array {
                $processed[] = $source . ':' . $index;

                return ['created' => 1, 'updated' => 0, 'skipped' => 0];
            }
        );

        $orderRepository = $this->createMock(OrderRepository::class);
        $orderRepository->method('findLastUpdateBySource')->willReturn(null);

        $tester = new CommandTester(new SyncGatewayCommand(
            $syncAccountService,
            $sourceConnection,
            $customerReader,
            $orderReader,
            $mapperService,
            $orderRepository,
        ));

        $status = $tester->execute([]);
        $display = $tester->getDisplay();

        self::assertSame(Command::FAILURE, $status);
        self::assertSame(['source_b:account', 'source_b:order'], $processed);
        self::assertStringContainsString('Sage : 3 compte(s) synchronisé(s).', $display);
        self::assertStringContainsString('source_a : connexion impossible', $display);
        self::assertStringContainsString('source_b : comptes 1/0, commandes 1/0, ignorés 0.', $display);
    }

    public function testFailingSageStepDoesNotStopSources(): void
    {
        $syncAccountService = $this->createMock(SyncAccountService::class);
        $syncAccountService->method('synchronize')->willThrowException(new \RuntimeException('API indisponible'));

        $sourceConnection = $this->createMock(SourceConnection::class);
        $sourceConnection->method('getEnabledSources')->willReturn(['source_a']);

        $customerReader = $this->createMock(SourceCustomerReader::class);
        $customerReader->expects($this->once())->method('readCustomers')->willReturn([]);

        $orderReader = $this->createMock(SourceOrderReader::class);
        $orderReader->method('readOrdersWithItems')->willReturn([]);

        $mapperService = $this->createMock(MapperService::class);
        $mapperService->method('run')->willReturn(['created' => 0, 'updated' => 0, 'skipped' => 0]);

        $orderRepository = $this->createMock(OrderRepository::class);

        $tester = new CommandTester(new SyncGatewayCommand(
            $syncAccountService,
            $sourceConnection,
            $customerReader,
            $orderReader,
            $mapperService,
            $orderRepository,
        ));

        self::assertSame(Command::FAILURE, $tester->execute([]));
        self::assertStringContainsString('sage : API indisponible', $tester->getDisplay());
        self::assertStringContainsString('source_a : comptes 0/0', $tester->getDisplay());
    }

    public function testAllStepsSucceed(): void
    {
        $syncAccountService = $this->createMock(SyncAccountService::class);
        $syncAccountService->method('synchronize')->willReturn(['total' => 0]);

        $sourceConnection = $this->createMock(SourceConnection::class);
        $sourceConnection->method('getEnabledSources')->willReturn(['source_a']);

        $customerReader = $this->createMock(SourceCustomerReader::class);
        $customerReader->method('readCustomers')->willReturn([]);

        $orderReader = $this->createMock(SourceOrderReader::class);
        $orderReader->method('readOrdersWithItems')->willReturn([]);

        $mapperService = $this->createMock(MapperService::class);
        $mapperService->method('run')->willReturn(['created' => 0, 'updated' => 0, 'skipped' => 0]);

        $tester = new CommandTester(new SyncGatewayCommand(
            $syncAccountService,
            $sourceConnection,
            $customerReader,
            $orderReader,
            $mapperService,
            $this->createMock(OrderRepository::class),
        ));

        self::assertSame(Command::SUCCESS, $tester->execute([]));
    }
}
