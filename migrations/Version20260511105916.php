<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260511105916 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add DEFAULT PENDING to member_subscriptions.status column';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE member_subscriptions MODIFY status VARCHAR(10) NOT NULL DEFAULT 'PENDING'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE member_subscriptions MODIFY status VARCHAR(10) NOT NULL');
    }
}
