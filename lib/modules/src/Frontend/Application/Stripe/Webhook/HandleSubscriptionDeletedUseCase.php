<?php
declare(strict_types=1);

namespace App\Frontend\Application\Stripe\Webhook;

use App\Frontend\Domain\Stripe\SubscriptionDeletedEvent;
use App\Shared\Domain\Stripe\AccountLinkingTokenRepository;
use Psr\Log\LoggerInterface;

final readonly class HandleSubscriptionDeletedUseCase
{
    public function __construct(
        private AccountLinkingTokenRepository $linkingTokenRepository,
        private LoggerInterface $logger,
    )
    {
    }

    public function __invoke(SubscriptionDeletedEvent $event): void
    {
        // Find the linking token for this subscription
        $linkingToken = $this->linkingTokenRepository->findByStripeSubscriptionId($event->stripeSubscriptionId);

        if (null === $linkingToken) {
            $this->logger->warning('No linking token found for deleted subscription', [
                'subscription_id' => $event->stripeSubscriptionId,
            ]);
            return;
        }

        // If it was linked to a Discord user, we could remove their roles here
        // For now, we just log it
        if ($linkingToken->isLinked()) {
            $this->logger->info('Subscription deleted for linked Discord user', [
                'subscription_id' => $event->stripeSubscriptionId,
                'discord_user_id' => $linkingToken->getDiscordUserId(),
                'customer_email' => $event->customerEmail,
            ]);

            // TODO: Remove Discord roles when role assignment is implemented
        } else {
            $this->logger->info('Subscription deleted before linking', [
                'subscription_id' => $event->stripeSubscriptionId,
                'customer_email' => $event->customerEmail,
            ]);
        }
    }
}
