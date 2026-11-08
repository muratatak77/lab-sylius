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

namespace Sylius\Component\Customer\Model;

class NewsletterSubscriptionLogEntry implements NewsletterSubscriptionLogEntryInterface
{
    /** @var mixed|null */
    protected $id;

    public function __construct(
        protected CustomerInterface $customer,
        protected bool $subscribed,
        protected \DateTimeInterface $loggedAt,
    ) {
    }

    public function getId()
    {
        return $this->id;
    }

    public function getCustomer(): CustomerInterface
    {
        return $this->customer;
    }

    public function isSubscribed(): bool
    {
        return $this->subscribed;
    }

    public function getLoggedAt(): \DateTimeInterface
    {
        return $this->loggedAt;
    }
}
