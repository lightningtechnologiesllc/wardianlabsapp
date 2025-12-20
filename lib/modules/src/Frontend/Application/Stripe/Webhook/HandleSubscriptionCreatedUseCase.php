<?php
declare(strict_types=1);

namespace App\Frontend\Application\Stripe\Webhook;

use App\Admin\Application\Tenant\FindTenantByStripePriceIdUseCase;
use App\Admin\Infrastructure\Provider\Stripe\Oauth2ClientAccountStripeProvider;
use App\Frontend\Domain\Stripe\SubscriptionCreatedEvent;
use App\Shared\Domain\Stripe\AccountLinkingToken;
use App\Shared\Domain\Stripe\AccountLinkingTokenRepository;
use App\Shared\Domain\Stripe\StripeAccountRepository;
use Psr\Log\LoggerInterface;

final readonly class HandleSubscriptionCreatedUseCase
{
    public function __construct(
        private FindTenantByStripePriceIdUseCase $findTenantByStripePriceIdUseCase,
        private AccountLinkingTokenRepository $linkingTokenRepository,
        private AccountLinkingEmailService $emailService,
        private Oauth2ClientAccountStripeProvider $stripeProvider,
        private StripeAccountRepository $stripeAccountRepository,
        private LoggerInterface $logger,
    )
    {
    }

    public function __invoke(SubscriptionCreatedEvent $event): void
    {
        // Find tenant by price ID
        $tenant = ($this->findTenantByStripePriceIdUseCase)($event->stripePriceId);

        if (null === $tenant) {
            $this->logger->warning('No tenant found for price ID', [
                'price_id' => $event->stripePriceId,
                'subscription_id' => $event->stripeSubscriptionId,
            ]);
            return;
        }

        // Get tenant's Stripe account
        $stripeAccounts = $this->stripeAccountRepository->findByTenantId($tenant->getId());

        if ($stripeAccounts->isEmpty()) {
            $this->logger->error('No Stripe account found for tenant', [
                'tenant_id' => $tenant->getId()->value(),
            ]);
            return;
        }

        $stripeAccount = $stripeAccounts->first();

        // Fetch customer email from Stripe
        $customerEmail = $this->stripeProvider->getCustomerEmail($stripeAccount, $event->stripeCustomerId);

        if (empty($customerEmail)) {
            $this->logger->warning('Could not fetch customer email from Stripe', [
                'subscription_id' => $event->stripeSubscriptionId,
                'customer_id' => $event->stripeCustomerId,
            ]);
            return;
        }

        // Update event with customer email
        $eventWithEmail = $event->withCustomerEmail($customerEmail);

        // Create linking token
        $linkingToken = AccountLinkingToken::create(
            $tenant->getId(),
            $eventWithEmail->stripeSubscriptionId,
            $eventWithEmail->customerEmail,
            $eventWithEmail->stripePriceId
        );

        $this->linkingTokenRepository->save($linkingToken);

        $this->logger->info('Created account linking token', [
            'subscription_id' => $eventWithEmail->stripeSubscriptionId,
            'customer_email' => $eventWithEmail->customerEmail,
            'tenant_id' => $tenant->getId()->value(),
        ]);

        // Send linking email
        ($this->emailService)($tenant, $linkingToken);
    }
}
