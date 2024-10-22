<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240122100504 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

        foreach ($days as $day) {
            $this->addSql(sprintf(
                "UPDATE user SET %s = CASE WHEN %s = 'op kantoor' THEN 0 WHEN %s = 'van thuis' THEN 1 WHEN %s = 'afwezig' THEN 2 ELSE %s END",
                $day,
                $day,
                $day,
                $day,
                $day
            ));
        }

        // Modify the table structure if needed
        $this->addSql('ALTER TABLE user CHANGE monday monday INT DEFAULT NULL, CHANGE tuesday tuesday INT DEFAULT NULL, CHANGE wednesday wednesday INT DEFAULT NULL, CHANGE thursday thursday INT DEFAULT NULL, CHANGE friday friday INT DEFAULT NULL');
    }



    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user CHANGE monday monday VARCHAR(255) DEFAULT NULL, CHANGE tuesday tuesday VARCHAR(255) DEFAULT NULL, CHANGE wednesday wednesday VARCHAR(255) DEFAULT NULL, CHANGE thursday thursday VARCHAR(255) DEFAULT NULL, CHANGE friday friday VARCHAR(255) DEFAULT NULL');
    }
}
