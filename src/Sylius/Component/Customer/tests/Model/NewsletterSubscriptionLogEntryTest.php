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

namespace Tests\Sylius\Component\Customer\Model;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Customer\Model\CustomerInterface;
use Sylius\Component\Customer\Model\NewsletterSubscriptionLogEntry;
use Sylius\Component\Customer\Model\NewsletterSubscriptionLogEntryInterface;

#[AllowMockObjectsWithoutExpectations]
final class NewsletterSubscriptionLogEntryTest extends TestCase
{
    public function testImplementsNewsletterSubscriptionLogEntryInterface(): void
    {
        $logEntry = new NewsletterSubscriptionLogEntry($this->createMock(CustomerInterface::class), true, new \DateTime());

        self::assertInstanceOf(NewsletterSubscriptionLogEntryInterface::class, $logEntry);
    }

    public function testHasNoIdByDefault(): void
    {
        $logEntry = new NewsletterSubscriptionLogEntry($this->createMock(CustomerInterface::class), true, new \DateTime());

        self::assertNull($logEntry->getId());
    }

    public function testExposesCustomerSubscriptionStateAndLogDate(): void
    {
        $customer = $this->createMock(CustomerInterface::class);
        $loggedAt = new \DateTime('2025-03-01 08:30:00');

        $logEntry = new NewsletterSubscriptionLogEntry($customer, false, $loggedAt);

        self::assertSame($customer, $logEntry->getCustomer());
        self::assertFalse($logEntry->isSubscribed());
        self::assertSame($loggedAt, $logEntry->getLoggedAt());
    }
}
