<?php
declare(strict_types=1);

namespace App\Admin\Application\Stripe\UseCase;

use App\Shared\Domain\Stripe\StripeAccount;
use App\Shared\Domain\Stripe\StripeAccountRepository;
use Psr\Log\LoggerInterface;
use Stripe\StripeClient;

final readonly class CreateWebhookEndpointUseCase
{
    public function __construct(
        private StripeAccountRepository $accountRepository,
        private LoggerInterface $logger,
        private string $webhookUrl,
    )
    {
    }

    public function __invoke(StripeAccount $account): void
    {
        // Skip if webhook already exists
        if ($account->getWebhookEndpointId()) {
            $this->logger->info('Webhook endpoint already exists for account', [
                'account_id' => $account->getAccountId()->value(),
                'webhook_endpoint_id' => $account->getWebhookEndpointId(),
            ]);
            return;
        }

        try {
            // Create Stripe client with the connected account's access token
            $stripeClient = new StripeClient($account->getAccessToken()->accessToken);

            // Create webhook endpoint
            $webhookEndpoint = $stripeClient->webhookEndpoints->create([
                'url' => $this->webhookUrl,
                'enabled_events' => [
                    'customer.subscription.created',
                    'customer.subscription.updated',
                    'customer.subscription.deleted',
                ],
                'description' => 'Wardian App Subscription Webhooks',
            ]);

            // Update account with webhook info
            $account->setWebhookSecret($webhookEndpoint->secret);
            $account->setWebhookEndpointId($webhookEndpoint->id);

            // Save to database
            $this->accountRepository->save($account);

            $this->logger->info('Created webhook endpoint for Stripe account', [
                'account_id' => $account->getAccountId()->value(),
                'stripe_account_id' => $account->getStripeProviderAccountId(),
                'webhook_endpoint_id' => $webhookEndpoint->id,
                'webhook_url' => $this->webhookUrl,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to create webhook endpoint', [
                'account_id' => $account->getAccountId()->value(),
                'stripe_account_id' => $account->getStripeProviderAccountId(),
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Failed to create webhook endpoint: ' . $e->getMessage());
        }
    }
}
