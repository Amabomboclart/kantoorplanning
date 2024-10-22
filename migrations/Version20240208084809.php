<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240208084809 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE locatie CHANGE date date VARCHAR(255) NOT NULL, CHANGE username username VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE user ADD first_name VARCHAR(255) NOT NULL, CHANGE id id VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE locatie CHANGE username username VARCHAR(307) NOT NULL, CHANGE date date DATE NOT NULL');
        $this->addSql('ALTER TABLE user DROP first_name, CHANGE id id VARCHAR(200) NOT NULL');
    }
}
