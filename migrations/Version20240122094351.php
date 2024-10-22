<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240122094351 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE locatie SET locatie_ = 0 WHERE locatie_ = 'op kantoor'");
        $this->addSql("UPDATE locatie SET locatie_ = 1 WHERE locatie_ = 'van thuis'");
        $this->addSql("UPDATE locatie SET locatie_ = 2 WHERE locatie_ = 'afwezig'");

        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE locatie CHANGE locatie_ locatie_ INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE locatie CHANGE locatie_ locatie_ VARCHAR(255) DEFAULT NULL');
    }
}
