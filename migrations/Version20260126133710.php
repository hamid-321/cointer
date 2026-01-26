<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260126133710 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE coin (id INT AUTO_INCREMENT NOT NULL, coin_gecko_id VARCHAR(50) NOT NULL, symbol VARCHAR(10) NOT NULL, name VARCHAR(100) NOT NULL, price NUMERIC(20, 10) NOT NULL, market_cap NUMERIC(25, 2) NOT NULL, change_24h DOUBLE PRECISION DEFAULT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE coin_history (id INT AUTO_INCREMENT NOT NULL, price NUMERIC(20, 10) NOT NULL, date DATE NOT NULL, coin_id INT NOT NULL, INDEX IDX_68B621BA84BBDA7 (coin_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE coin_history ADD CONSTRAINT FK_68B621BA84BBDA7 FOREIGN KEY (coin_id) REFERENCES coin (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE coin_history DROP FOREIGN KEY FK_68B621BA84BBDA7');
        $this->addSql('DROP TABLE coin');
        $this->addSql('DROP TABLE coin_history');
    }
}
