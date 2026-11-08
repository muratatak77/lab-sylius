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

namespace Sylius\Tests\Api\Admin;

use PHPUnit\Framework\Attributes\Test;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Sylius\Tests\Api\JsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

final class CustomerNewsletterSubscriptionLogEntriesTest extends JsonApiTestCase
{
    #[Test]
    public function it_denies_access_to_newsletter_subscription_log_entries_for_not_authenticated_user(): void
    {
        $fixtures = $this->loadFixturesFromFiles(['customer.yaml', 'customer/newsletter_subscription_log_entries.yaml']);

        /** @var CustomerInterface $customer */
        $customer = $fixtures['customer_tony'];

        $this->client->request(
            method: 'GET',
            uri: sprintf('/api/v2/admin/customers/%d/newsletter-subscription-log-entries', $customer->getId()),
            server: $this->headerBuilder()->withJsonLdAccept()->build(),
        );

        $this->assertResponseCode($this->client->getResponse(), Response::HTTP_UNAUTHORIZED);
    }

    #[Test]
    public function it_gets_newsletter_subscription_log_entries_of_a_customer(): void
    {
        $fixtures = $this->loadFixturesFromFiles([
            'authentication/api_administrator.yaml',
            'customer.yaml',
            'customer/newsletter_subscription_log_entries.yaml',
        ]);

        /** @var CustomerInterface $customer */
        $customer = $fixtures['customer_tony'];

        $this->client->request(
            method: 'GET',
            uri: sprintf('/api/v2/admin/customers/%d/newsletter-subscription-log-entries', $customer->getId()),
            server: $this->headerBuilder()->withJsonLdAccept()->withAdminUserAuthorization('api@example.com')->build(),
        );

        $this->assertResponse(
            $this->client->getResponse(),
            'admin/customer/get_customer_newsletter_subscription_log_entries_response',
            Response::HTTP_OK,
        );
    }

    #[Test]
    public function it_logs_subscribing_to_and_unsubscribing_from_the_newsletter_when_updating_a_customer(): void
    {
        $fixtures = $this->loadFixturesFromFiles(['authentication/api_administrator.yaml', 'customer.yaml']);
        $header = $this->headerBuilder()->withJsonLdContentType()->withJsonLdAccept()->withAdminUserAuthorization('api@example.com')->build();

        /** @var CustomerInterface $customer */
        $customer = $fixtures['customer_dave'];
        $customerUri = sprintf('/api/v2/admin/customers/%d', $customer->getId());

        $this->client->request(
            method: 'PUT',
            uri: $customerUri,
            server: $header,
            content: json_encode(['subscribedToNewsletter' => true], \JSON_THROW_ON_ERROR),
        );
        $this->assertResponseCode($this->client->getResponse(), Response::HTTP_OK);

        $this->client->request(
            method: 'PUT',
            uri: $customerUri,
            server: $header,
            content: json_encode(['firstName' => 'David'], \JSON_THROW_ON_ERROR),
        );
        $this->assertResponseCode($this->client->getResponse(), Response::HTTP_OK);

        $this->client->request(
            method: 'PUT',
            uri: $customerUri,
            server: $header,
            content: json_encode(['subscribedToNewsletter' => false], \JSON_THROW_ON_ERROR),
        );
        $this->assertResponseCode($this->client->getResponse(), Response::HTTP_OK);

        $this->client->request(
            method: 'GET',
            uri: $customerUri . '/newsletter-subscription-log-entries',
            server: $header,
        );

        $this->assertResponse(
            $this->client->getResponse(),
            'admin/customer/get_customer_newsletter_subscription_log_entries_after_update_response',
            Response::HTTP_OK,
        );
    }

    #[Test]
    public function it_logs_the_subscription_only_for_customers_created_as_subscribed(): void
    {
        $this->loadFixturesFromFiles(['authentication/api_administrator.yaml']);
        $header = $this->headerBuilder()->withJsonLdContentType()->withJsonLdAccept()->withAdminUserAuthorization('api@example.com')->build();

        $this->client->request(
            method: 'POST',
            uri: '/api/v2/admin/customers',
            server: $header,
            content: json_encode(['email' => 'subscriber@example.com', 'subscribedToNewsletter' => true], \JSON_THROW_ON_ERROR),
        );
        $this->assertResponseCode($this->client->getResponse(), Response::HTTP_CREATED);

        $this->client->request(
            method: 'POST',
            uri: '/api/v2/admin/customers',
            server: $header,
            content: json_encode(['email' => 'not-a-subscriber@example.com'], \JSON_THROW_ON_ERROR),
        );
        $this->assertResponseCode($this->client->getResponse(), Response::HTTP_CREATED);

        /** @var RepositoryInterface $logEntryRepository */
        $logEntryRepository = $this->getContainer()->get('sylius.repository.newsletter_subscription_log_entry');
        /** @var RepositoryInterface $customerRepository */
        $customerRepository = $this->getContainer()->get('sylius.repository.customer');

        $subscriber = $customerRepository->findOneBy(['email' => 'subscriber@example.com']);
        $notASubscriber = $customerRepository->findOneBy(['email' => 'not-a-subscriber@example.com']);

        $this->assertCount(1, $logEntryRepository->findBy(['customer' => $subscriber, 'subscribed' => true]));
        $this->assertCount(0, $logEntryRepository->findBy(['customer' => $notASubscriber]));
        $this->assertNotNull($subscriber->getSubscribedToNewsletterAt());
        $this->assertNull($notASubscriber->getSubscribedToNewsletterAt());
    }
}
