<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261007120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Complète les comptes, les plateformes et les index de synchronisation.';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('account')) {
            $account = $schema->getTable('account');

            if (!$account->hasColumn('address')) {
                $this->addSql('ALTER TABLE account ADD address VARCHAR(255) DEFAULT NULL');
            }
            if (!$account->hasColumn('address_complement')) {
                $this->addSql('ALTER TABLE account ADD address_complement VARCHAR(255) DEFAULT NULL');
            }
            if (!$account->hasColumn('notification_sent_at')) {
                $this->addSql('ALTER TABLE account ADD notification_sent_at DATETIME DEFAULT NULL');
            }
            if ($account->hasColumn('siret') && $account->getColumn('siret')->getLength() !== 32) {
                $this->addSql('ALTER TABLE account CHANGE siret siret VARCHAR(32) DEFAULT NULL');
            }
            if (!$account->hasIndex('IDX_ACCOUNT_EMAIL')) {
                $this->addSql('CREATE INDEX IDX_ACCOUNT_EMAIL ON account (email)');
            }
            if (!$account->hasIndex('IDX_ACCOUNT_CT_NUMBER')) {
                $this->addSql('CREATE INDEX IDX_ACCOUNT_CT_NUMBER ON account (ct_number)');
            }
        }

        if ($schema->hasTable('order')) {
            $order = $schema->getTable('order');

            if (!$order->hasColumn('platform')) {
                $this->addSql("ALTER TABLE `order` ADD platform VARCHAR(20) DEFAULT 'b2c' NOT NULL");
            }
            if (!$order->hasIndex('IDX_ORDER_SOURCE')) {
                $this->addSql('CREATE INDEX IDX_ORDER_SOURCE ON `order` (source, source_order_id)');
            }
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('order') && $schema->getTable('order')->hasColumn('platform')) {
            $this->addSql('ALTER TABLE `order` DROP platform');
        }
        if ($schema->hasTable('account')) {
            $this->addSql('ALTER TABLE account DROP address, DROP address_complement');
        }
    }
}
