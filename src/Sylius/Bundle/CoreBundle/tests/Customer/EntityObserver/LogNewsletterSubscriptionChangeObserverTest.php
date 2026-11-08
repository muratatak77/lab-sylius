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

namespace Tests\Sylius\Bundle\CoreBundle\Customer\EntityObserver;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\CoreBundle\Customer\EntityObserver\LogNewsletterSubscriptionChangeObserver;
use Sylius\Bundle\CoreBundle\PriceHistory\EntityObserver\EntityObserverInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Customer\Factory\NewsletterSubscriptionLogEntryFactoryInterface;
use Sylius\Component\Customer\Model\Customer;
use Sylius\Component\Customer\Model\CustomerInterface;
use Sylius\Component\Customer\Model\NewsletterSubscriptionLogEntryInterface;
use Symfony\Component\Clock\MockClock;

#[AllowMockObjectsWithoutExpectations]
final class LogNewsletterSubscriptionChangeObserverTest extends TestCase
{
    private MockObject&NewsletterSubscriptionLogEntryFactoryInterface $logEntryFactory;

    private EntityManagerInterface&MockObject $entityManager;

    private MockClock $clock;

    private LogNewsletterSubscriptionChangeObserver $observer;

    protected function setUp(): void
    {
        $this->logEntryFactory = $this->createMock(NewsletterSubscriptionLogEntryFactoryInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->clock = new MockClock('2025-06-15 18:45:00');

        $this->observer = new LogNewsletterSubscriptionChangeObserver(
            $this->logEntryFactory,
            $this->entityManager,
            $this->clock,
        );
    }

    public function testImplementsEntityObserverInterface(): void
    {
        $this->assertInstanceOf(EntityObserverInterface::class, $this->observer);
    }

    public function testObservesSubscribedToNewsletterField(): void
    {
        $this->assertSame(['subscribedToNewsletter'], $this->observer->observedFields());
    }

    public function testDoesNotSupportEntitiesOtherThanCustomer(): void
    {
        $this->entityManager->expects($this->never())->method('getUnitOfWork');

        $this->assertFalse($this->observer->supports($this->createStub(ChannelInterface::class)));
    }

    public function testSupportsSubscribedCustomer(): void
    {
        $customer = $this->createConfiguredStub(CustomerInterface::class, ['isSubscribedToNewsletter' => true]);

        $this->entityManager->expects($this->never())->method('getUnitOfWork');

        $this->assertTrue($this->observer->supports($customer));
    }

    public function testSupportsExistingCustomerThatUnsubscribed(): void
    {
        $customer = $this->createConfiguredStub(CustomerInterface::class, ['isSubscribedToNewsletter' => false]);

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork->expects($this->once())->method('isScheduledForInsert')->with($customer)->willReturn(false);
        $this->entityManager->expects($this->once())->method('getUnitOfWork')->willReturn($unitOfWork);

        $this->assertTrue($this->observer->supports($customer));
    }

    public function testDoesNotSupportNewCustomerThatIsNotSubscribed(): void
    {
        $customer = $this->createConfiguredStub(CustomerInterface::class, ['isSubscribedToNewsletter' => false]);

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork->expects($this->once())->method('isScheduledForInsert')->with($customer)->willReturn(true);
        $this->entityManager->expects($this->once())->method('getUnitOfWork')->willReturn($unitOfWork);

        $this->assertFalse($this->observer->supports($customer));
    }

    public function testPersistsLogEntryForChangedCustomer(): void
    {
        $customer = $this->createStub(CustomerInterface::class);
        $logEntry = $this->createStub(NewsletterSubscriptionLogEntryInterface::class);

        $this->logEntryFactory
            ->expects($this->once())
            ->method('createForCustomer')
            ->with($customer, new \DateTime('2025-06-15 18:45:00'))
            ->willReturn($logEntry)
        ;
        $this->entityManager->expects($this->once())->method('persist')->with($logEntry);

        $this->observer->onChange($customer);
    }

    public function testLogsSubscriptionAtTheClockDateForCustomerWithoutSubscribedToNewsletterAt(): void
    {
        $customer = $this->createConfiguredStub(CustomerInterface::class, ['isSubscribedToNewsletter' => true]);
        $logEntry = $this->createStub(NewsletterSubscriptionLogEntryInterface::class);

        $this->assertFalse(method_exists($customer, 'getSubscribedToNewsletterAt'));

        $this->logEntryFactory
            ->expects($this->once())
            ->method('createForCustomer')
            ->with($customer, new \DateTime('2025-06-15 18:45:00'))
            ->willReturn($logEntry)
        ;
        $this->entityManager->expects($this->once())->method('persist')->with($logEntry);

        $this->observer->onChange($customer);
    }

    public function testLogsSubscriptionAtTheCustomerSubscribedToNewsletterAtDate(): void
    {
        $customer = new Customer();
        $customer->setSubscribedToNewsletterAt(new \DateTime('2025-01-10 10:00:00'));
        $customer->setSubscribedToNewsletter(true);
        $logEntry = $this->createStub(NewsletterSubscriptionLogEntryInterface::class);

        $this->logEntryFactory
            ->expects($this->once())
            ->method('createForCustomer')
            ->with($customer, $this->callback(
                static fn (\DateTimeInterface $loggedAt): bool => $loggedAt == new \DateTime('2025-01-10 10:00:00') &&
                    $loggedAt !== $customer->getSubscribedToNewsletterAt(),
            ))
            ->willReturn($logEntry)
        ;
        $this->entityManager->expects($this->once())->method('persist')->with($logEntry);

        $this->observer->onChange($customer);
    }

    public function testLogsUnsubscriptionAtTheClockDate(): void
    {
        $customer = new Customer();
        $customer->setSubscribedToNewsletter(true);
        $customer->setSubscribedToNewsletter(false);
        $logEntry = $this->createStub(NewsletterSubscriptionLogEntryInterface::class);

        $this->logEntryFactory
            ->expects($this->once())
            ->method('createForCustomer')
            ->with($customer, new \DateTime('2025-06-15 18:45:00'))
            ->willReturn($logEntry)
        ;
        $this->entityManager->expects($this->once())->method('persist')->with($logEntry);

        $this->observer->onChange($customer);
    }

    public function testThrowsExceptionIfEntityIsNotCustomer(): void
    {
        $this->logEntryFactory->expects($this->never())->method('createForCustomer');
        $this->entityManager->expects($this->never())->method('persist');

        $this->expectException(\InvalidArgumentException::class);

        $this->observer->onChange($this->createStub(ChannelInterface::class));
    }
}
