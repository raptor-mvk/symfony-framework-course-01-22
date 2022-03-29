<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220329145542 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE email_notification (id BIGSERIAL NOT NULL, email VARCHAR(128) NOT NULL, text VARCHAR(512) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE feed (id BIGSERIAL NOT NULL, reader_id BIGINT DEFAULT NULL, tweets JSON DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_234044AB1717D737 ON feed (reader_id)');
        $this->addSql('CREATE TABLE sms_notification (id BIGSERIAL NOT NULL, phone VARCHAR(11) NOT NULL, text VARCHAR(60) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE feed ADD CONSTRAINT FK_234044AB1717D737 FOREIGN KEY (reader_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "user" ADD phone VARCHAR(11) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD email VARCHAR(128) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD preferred VARCHAR(10) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE email_notification');
        $this->addSql('DROP TABLE feed');
        $this->addSql('DROP TABLE sms_notification');
        $this->addSql('ALTER TABLE "user" DROP phone');
        $this->addSql('ALTER TABLE "user" DROP email');
        $this->addSql('ALTER TABLE "user" DROP preferred');
    }
}
