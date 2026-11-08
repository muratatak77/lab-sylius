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

namespace Tests\Sylius\Component\Customer\Factory;

use PHPUnit\Framework\TestCase;
use Sylius\Component\Customer\Factory\NewsletterSubscriptionLogEntryFactory;
use Sylius\Component\Customer\Factory\NewsletterSubscriptionLogEntryFactoryInterface;
use Sylius\Component\Customer\Model\Customer;
use Sylius\Component\Customer\Model\NewsletterSubscriptionLogEntry;
use Sylius\Resource\Exception\UnsupportedMethodException;
use Sylius\Resource\Model\ResourceInterface;

final class NewsletterSubscriptionLogEntryFactoryTest extends TestCase
{
    /** @var NewsletterSubscriptionLogEntryFactory<NewsletterSubscriptionLogEntry> */
    private NewsletterSubscriptionLogEntryFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new NewsletterSubscriptionLogEntryFactory(NewsletterSubscriptionLogEntry::class);
    }

    public function testImplementsNewsletterSubscriptionLogEntryFactoryInterface(): void
    {
        self::assertInstanceOf(NewsletterSubscriptionLogEntryFactoryInterface::class, $this->factory);
    }

    public function testThrowsExceptionWhenInvalidClassNameIsPassed(): void
    {
        $this->expectException(\DomainException::class);

        new NewsletterSubscriptionLogEntryFactory(ResourceInterface::class);
    }

    public function testThrowsExceptionWhenCreateNewIsCalled(): void
    {
        $this->expectException(UnsupportedMethodException::class);

        $this->factory->createNew();
    }

    public function testCreatesLogEntryForSubscribedCustomer(): void
    {
        $customer = new Customer();
        $customer->setSubscribedToNewsletter(true);
        $loggedAt = new \DateTime('2025-06-15 18:45:00');

        self::assertEquals(
            new NewsletterSubscriptionLogEntry($customer, true, $loggedAt),
            $this->factory->createForCustomer($customer, $loggedAt),
        );
    }

    public function testCreatesLogEntryForUnsubscribedCustomer(): void
    {
        $customer = new Customer();
        $loggedAt = new \DateTime('2025-03-01 08:30:00');

        self::assertEquals(
            new NewsletterSubscriptionLogEntry($customer, false, $loggedAt),
            $this->factory->createForCustomer($customer, $loggedAt),
        );
    }
}
