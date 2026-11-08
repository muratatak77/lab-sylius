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

namespace Sylius\Component\Customer\Factory;

use Sylius\Component\Customer\Model\CustomerInterface;
use Sylius\Component\Customer\Model\NewsletterSubscriptionLogEntryInterface;
use Sylius\Resource\Exception\UnsupportedMethodException;

/**
 * @template T of NewsletterSubscriptionLogEntryInterface
 *
 * @implements NewsletterSubscriptionLogEntryFactoryInterface<T>
 */
final class NewsletterSubscriptionLogEntryFactory implements NewsletterSubscriptionLogEntryFactoryInterface
{
    public function __construct(private string $className)
    {
        if (!is_a($className, NewsletterSubscriptionLogEntryInterface::class, true)) {
            throw new \DomainException(sprintf(
                'This factory requires %s or its descend to be used as resource',
                NewsletterSubscriptionLogEntryInterface::class,
            ));
        }
    }

    /**
     * @return T
     *
     * @throws UnsupportedMethodException
     */
    public function createNew(): object
    {
        throw new UnsupportedMethodException('createNew');
    }

    /**
     * @return T
     */
    public function createForCustomer(
        CustomerInterface $customer,
        \DateTimeInterface $loggedAt,
    ): NewsletterSubscriptionLogEntryInterface {
        /** @var NewsletterSubscriptionLogEntryInterface $logEntry */
        $logEntry = new $this->className($customer, $customer->isSubscribedToNewsletter(), $loggedAt);

        return $logEntry;
    }
}
