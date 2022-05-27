<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220524145126 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feed DROP CONSTRAINT fk_234044ab1717d737');
        $this->addSql('ALTER TABLE feed ALTER reader_id SET NOT NULL');
        $this->addSql('ALTER TABLE "user" ADD is_protected BOOLEAN DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feed ALTER reader_id DROP NOT NULL');
        $this->addSql('ALTER TABLE feed ADD CONSTRAINT fk_234044ab1717d737 FOREIGN KEY (reader_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "user" DROP is_protected');
    }
}
