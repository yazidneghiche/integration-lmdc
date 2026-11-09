<?php

namespace App\Tests\Service;

use App\Service\SourceConnection;
use App\Service\SourceOrderReader;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

class SourceOrderReaderTest extends TestCase
{
    public function testOrderQueryWithoutCtNumber(): void
    {
        $reader = new SourceOrderReader($this->createMock(SourceConnection::class));

        $sql = $reader->buildOrderQuery('ps_', false);

        self::assertStringNotContainsString('ct_number', $sql);
        self::assertStringContainsString('c.company,', $sql);
        self::assertStringContainsString('c.email', $sql);
        self::assertStringContainsString('FROM ps_orders o', $sql);
        self::assertStringContainsString('LEFT JOIN ps_customer c', $sql);
        self::assertStringContainsString('WHERE o.date_upd > :lastUpdate', $sql);
    }

    public function testOrderQueryWithCtNumber(): void
    {
        $reader = new SourceOrderReader($this->createMock(SourceConnection::class));

        $sql = $reader->buildOrderQuery('shop_', true);

        self::assertMatchesRegularExpression('/c\.company,\s+c\.ct_number,\s+c\.email/', $sql);
        self::assertStringContainsString('FROM shop_orders o', $sql);
        self::assertStringContainsString('LEFT JOIN shop_customer c', $sql);
    }

    public function testReadOrdersWithItemsUsesOneConnectionAndDetectsColumnOnce(): void
    {
        $columnStatement = $this->createMock(PDOStatement::class);
        $columnStatement->method('fetch')->willReturn(false);

        $ordersStatement = $this->createMock(PDOStatement::class);
        $ordersStatement->method('execute')->willReturn(true);
        $ordersStatement->method('fetchAll')->willReturn([
            ['id_order' => 1, 'email' => 'a@example.test'],
            ['id_order' => 2, 'email' => 'b@example.test'],
        ]);

        $itemsStatement = $this->createMock(PDOStatement::class);
        $itemsStatement->method('execute')->willReturn(true);
        $itemsStatement->method('fetchAll')->willReturn([['id_order_detail' => 10]]);

        $preparedSql = [];
        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('query')
            ->with("SHOW COLUMNS FROM `ps_customer` LIKE 'ct_number'")
            ->willReturn($columnStatement);
        $pdo->method('prepare')->willReturnCallback(
            static function (string $sql) use (&$preparedSql, $ordersStatement, $itemsStatement): PDOStatement {
                $preparedSql[] = $sql;

                return str_contains($sql, 'order_detail') ? $itemsStatement : $ordersStatement;
            }
        );

        $connection = $this->createMock(SourceConnection::class);
        $connection->expects($this->exactly(2))->method('connect')->with('shop')->willReturn($pdo);
        $connection->method('getConfig')->willReturn(['prefix' => 'ps_']);

        $reader = new SourceOrderReader($connection);

        $result = $reader->readOrdersWithItems('shop');
        // Deuxième lecture : nouvelle connexion, mais la colonne n'est pas revérifiée
        $reader->readOrdersWithItems('shop');

        self::assertCount(2, $result);
        self::assertSame([['id_order_detail' => 10]], $result[0]['items']);
        self::assertStringNotContainsString('ct_number', $preparedSql[0]);
    }

    public function testCtNumberIsSelectedWhenColumnExists(): void
    {
        $columnStatement = $this->createMock(PDOStatement::class);
        $columnStatement->method('fetch')->willReturn(['Field' => 'ct_number']);

        $ordersStatement = $this->createMock(PDOStatement::class);
        $ordersStatement->method('execute')->willReturn(true);
        $ordersStatement->method('fetchAll')->willReturn([]);

        $preparedSql = [];
        $pdo = $this->createMock(PDO::class);
        $pdo->method('query')->willReturn($columnStatement);
        $pdo->method('prepare')->willReturnCallback(
            static function (string $sql) use (&$preparedSql, $ordersStatement): PDOStatement {
                $preparedSql[] = $sql;

                return $ordersStatement;
            }
        );

        $connection = $this->createMock(SourceConnection::class);
        $connection->method('connect')->willReturn($pdo);
        $connection->method('getConfig')->willReturn(['prefix' => 'ps_']);

        (new SourceOrderReader($connection))->readOrders('shop');

        self::assertStringContainsString('c.ct_number,', $preparedSql[0]);
    }
}
