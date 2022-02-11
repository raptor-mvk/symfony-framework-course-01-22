<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20210212155210 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE tweet (id BIGSERIAL NOT NULL, author_id BIGINT DEFAULT NULL, text VARCHAR(140) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE "user" (id BIGSERIAL NOT NULL, login VARCHAR(32) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE tweet ADD CONSTRAINT tweet__author_id__fk FOREIGN KEY (author_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE TABLE subscription (id BIGSERIAL NOT NULL, author_id BIGINT DEFAULT NULL, follower_id BIGINT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX subscription__author_id__idx ON subscription (author_id)');
        $this->addSql('CREATE INDEX subscription__follower_id__idx ON subscription (follower_id)');
        $this->addSql('CREATE UNIQUE INDEX subscription__author_id__follower_id__uniq ON subscription (author_id, follower_id)');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT subscription__author_id__fk FOREIGN KEY (author_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT subscription__follower_id__fk FOREIGN KEY (follower_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE TABLE author_follower (author_id BIGINT DEFAULT NULL, follower_id BIGINT DEFAULT NULL, PRIMARY KEY(author_id, follower_id))');
        $this->addSql('CREATE INDEX author_follower__author_id__idx ON author_follower (author_id)');
        $this->addSql('CREATE INDEX author_follower__follower_id__idx ON author_follower (follower_id)');
        $this->addSql('CREATE UNIQUE INDEX author_folllower__author_id__follower_id__uniq ON author_follower (author_id, follower_id)');
        $this->addSql('ALTER TABLE author_follower ADD CONSTRAINT author_follower__author_id__fk FOREIGN KEY (author_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE author_follower ADD CONSTRAINT author_follower__follower_id__fk FOREIGN KEY (follower_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE TABLE feed (id BIGSERIAL NOT NULL, reader_id BIGINT DEFAULT NULL, tweets JSON DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX feed__reader_id__uniq ON feed (reader_id)');
        $this->addSql('ALTER TABLE feed ADD CONSTRAINT feed__reader_id__fk FOREIGN KEY (reader_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE author_follower');
        $this->addSql('DROP TABLE subscription');
        $this->addSql('DROP TABLE tweet');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('DROP TABLE feed');
    }
}
