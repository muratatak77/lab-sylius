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
use Sylius\Resource\Factory\FactoryInterface;

/**
 * @template T of NewsletterSubscriptionLogEntryInterface
 *
 * @extends FactoryInterface<T>
 */
interface NewsletterSubscriptionLogEntryFactoryInterface extends FactoryInterface
{
    public function createForCustomer(
        CustomerInterface $customer,
        \DateTimeInterface $loggedAt,
    ): NewsletterSubscriptionLogEntryInterface;
}
