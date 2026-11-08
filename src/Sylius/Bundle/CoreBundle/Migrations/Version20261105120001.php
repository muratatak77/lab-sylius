<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Sylius Sp. z o.o.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sylius\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Sylius\Bundle\CoreBundle\Doctrine\Migrations\AbstractPostgreSQLMigration;

final class Version20261105120001 extends AbstractPostgreSQLMigration
{
    public function getDescription(): string
    {
        return 'Add subscribed_to_newsletter_at column to sylius_customer, create sylius_newsletter_subscription_log_entry table and backfill both for subscribed customers (PostgreSQL).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE sylius_newsletter_subscription_log_entry_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE sylius_newsletter_subscription_log_entry (id INT NOT NULL, customer_id INT NOT NULL, subscribed BOOLEAN NOT NULL, logged_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_63204A939395C3F3 ON sylius_newsletter_subscription_log_entry (customer_id)');
        $this->addSql('ALTER TABLE sylius_newsletter_subscription_log_entry ADD CONSTRAINT FK_63204A939395C3F3 FOREIGN KEY (customer_id) REFERENCES sylius_customer (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE sylius_customer ADD subscribed_to_newsletter_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');

        /*
         * The real opt-in moment of existing subscribers is unknown. The last update of the customer is the earliest
         * moment at which the subscription is known to have been active, so it is used instead of the creation date.
         */
        $this->addSql('UPDATE sylius_customer SET subscribed_to_newsletter_at = COALESCE(updated_at, created_at) WHERE subscribed_to_newsletter = true');
        $this->addSql('INSERT INTO sylius_newsletter_subscription_log_entry (id, customer_id, subscribed, logged_at) SELECT nextval(\'sylius_newsletter_subscription_log_entry_id_seq\'), id, true, subscribed_to_newsletter_at FROM (SELECT id, subscribed_to_newsletter_at FROM sylius_customer WHERE subscribed_to_newsletter = true ORDER BY id) AS subscribed_customer');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE sylius_newsletter_subscription_log_entry DROP CONSTRAINT FK_63204A939395C3F3');
        $this->addSql('DROP SEQUENCE sylius_newsletter_subscription_log_entry_id_seq CASCADE');
        $this->addSql('DROP TABLE sylius_newsletter_subscription_log_entry');
        $this->addSql('ALTER TABLE sylius_customer DROP subscribed_to_newsletter_at');
    }
}
