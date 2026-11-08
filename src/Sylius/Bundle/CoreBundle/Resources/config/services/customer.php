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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Sylius\Bundle\CoreBundle\Customer\EntityObserver\LogNewsletterSubscriptionChangeObserver;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services
        ->set('sylius.entity_observer.customer.log_newsletter_subscription_change', LogNewsletterSubscriptionChangeObserver::class)
        ->args([
            service('sylius.factory.newsletter_subscription_log_entry'),
            service('sylius.manager.newsletter_subscription_log_entry'),
            service('clock'),
        ])
        ->tag('sylius.entity_observer')
    ;
};
