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

namespace Sylius\Bundle\CoreBundle\Customer\EntityObserver;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\CoreBundle\PriceHistory\EntityObserver\EntityObserverInterface;
use Sylius\Component\Customer\Factory\NewsletterSubscriptionLogEntryFactoryInterface;
use Sylius\Component\Customer\Model\CustomerInterface;
use Sylius\Component\Customer\Model\NewsletterSubscriptionLogEntryInterface;
use Symfony\Component\Clock\ClockInterface;
use Webmozart\Assert\Assert;

final readonly class LogNewsletterSubscriptionChangeObserver implements EntityObserverInterface
{
    /** @param NewsletterSubscriptionLogEntryFactoryInterface<NewsletterSubscriptionLogEntryInterface> $logEntryFactory */
    public function __construct(
        private NewsletterSubscriptionLogEntryFactoryInterface $logEntryFactory,
        private EntityManagerInterface $entityManager,
        private ClockInterface $clock,
    ) {
    }

    public function onChange(object $entity): void
    {
        Assert::isInstanceOf($entity, CustomerInterface::class);

        $loggedAt = null;
        /*
         * A subscription is logged at the customer's "subscribed since" date, so both values match when the entry is
         * written. Later changes to subscribedToNewsletterAt alone are not observed and do not update the entry.
         */
        if ($entity->isSubscribedToNewsletter() && method_exists($entity, 'getSubscribedToNewsletterAt')) {
            $loggedAt = $entity->getSubscribedToNewsletterAt();
        }

        $logEntry = $this->logEntryFactory->createForCustomer(
            $entity,
            \DateTime::createFromInterface($loggedAt ?? $this->clock->now()),
        );

        $this->entityManager->persist($logEntry);
    }

    public function supports(object $entity): bool
    {
        if (!$entity instanceof CustomerInterface) {
            return false;
        }

        if ($entity->isSubscribedToNewsletter()) {
            return true;
        }

        /* A new customer always has the field in its change set; logging that would add an entry for every guest checkout. */
        return !$this->entityManager->getUnitOfWork()->isScheduledForInsert($entity);
    }

    public function observedFields(): array
    {
        return ['subscribedToNewsletter'];
    }
}
