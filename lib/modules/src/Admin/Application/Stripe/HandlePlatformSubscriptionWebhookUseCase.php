<?php
declare(strict_types=1);

namespace App\Admin\Application\Stripe;

use App\Admin\Application\Stripe\Message\ProcessSubscriptionCreatedMessage;
use App\Admin\Domain\User\AllowedPlatformPrices;
use App\Admin\Domain\User\PendingPlatformSubscriptionRepository;
use App\Admin\Domain\User\PlatformSubscription;
use App\Admin\Domain\User\UserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class HandlePlatformSubscriptionWebhookUseCase
{
    public function __construct(
        private UserRepository                        $userRepository,
        private PendingPlatformSubscriptionRepository $pendingPlatformSubscriptionRepository,
        private MessageBusInterface                   $messageBus,
        private LoggerInterface                       $logger,
        private AllowedPlatformPrices                 $allowedPlatformPrices,
    ) {
    }

    public function __invoke(array $event): void
    {
        $eventType = $event['type'];
        $eventData = $event['data']['object'];

        match ($eventType) {
            'customer.subscription.created' => $this->handleSubscriptionCreated($eventData),
            'customer.subscription.updated' => $this->handleSubscriptionUpdated($eventData),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($eventData),
            default => $this->logger->debug('Unhandled webhook event type', ['type' => $eventType]),
        };
    }

    private function handleSubscriptionCreated(array $eventData): void
    {
        $planId = $this->extractPlanId($eventData);

        if (!$this->allowedPlatformPrices->isAllowed($planId)) {
            $this->logger->warning('Subscription created with invalid price, ignoring', [
                'subscription_id' => $eventData['id'],
                'plan_id' => $planId,
                'allowed_prices' => $this->allowedPlatformPrices->all(),
            ]);
            return;
        }

        // Dispatch to queue for async processing with retry capability
        $this->messageBus->dispatch(new ProcessSubscriptionCreatedMessage($eventData));

        $this->logger->info('Subscription created event dispatched to queue', [
            'subscription_id' => $eventData['id'],
        ]);
    }

    private function handleSubscriptionUpdated(array $eventData): void
    {
        $planId = $this->extractPlanId($eventData);

        if (!$this->allowedPlatformPrices->isAllowed($planId)) {
            $this->logger->warning('Subscription updated with invalid price, ignoring', [
                'subscription_id' => $eventData['id'],
                'plan_id' => $planId,
                'allowed_prices' => $this->allowedPlatformPrices->all(),
            ]);
            return;
        }

        $subscriptionId = $eventData['id'];
        $platformSubscription = $this->createPlatformSubscriptionFromData($eventData);

        // Try to find pending subscription first (not yet redeemed)
        $pendingSubscription = $this->pendingPlatformSubscriptionRepository->findBySubscriptionId($subscriptionId);

        if ($pendingSubscription !== null) {
            // Update pending subscription (it hasn't been redeemed yet)
            $pendingSubscription->updateSubscription($platformSubscription);
            $this->pendingPlatformSubscriptionRepository->save($pendingSubscription);

            $this->logger->info('Pending platform subscription updated', [
                'subscription_id' => $subscriptionId,
                'status' => $eventData['status'],
            ]);
            return;
        }

        // Otherwise, try to find user with redeemed subscription
        $user = $this->userRepository->findBySubscriptionId($subscriptionId);

        if ($user === null) {
            $this->logger->error('Subscription not found (neither pending nor redeemed)', [
                'subscription_id' => $subscriptionId
            ]);
            return;
        }

        $user->setPlatformSubscription($platformSubscription);
        $this->userRepository->save($user);

        $this->logger->info('Platform subscription updated for user', [
            'user_id' => $user->id()->value(),
            'subscription_id' => $subscriptionId,
            'status' => $eventData['status'],
        ]);
    }

    private function handleSubscriptionDeleted(array $subscriptionData): void
    {
        $planId = $this->extractPlanId($subscriptionData);

        if (!$this->allowedPlatformPrices->isAllowed($planId)) {
            $this->logger->warning('Subscription deleted with invalid price, ignoring', [
                'subscription_id' => $subscriptionData['id'],
                'plan_id' => $planId,
                'allowed_prices' => $this->allowedPlatformPrices->all(),
            ]);
            return;
        }

        $subscriptionId = $subscriptionData['id'];
        $platformSubscription = $this->createPlatformSubscriptionFromData($subscriptionData);

        // Try to find pending subscription first
        $pendingSubscription = $this->pendingPlatformSubscriptionRepository->findBySubscriptionId($subscriptionId);

        if ($pendingSubscription !== null) {
            // Update pending subscription
            $pendingSubscription->updateSubscription($platformSubscription);
            $this->pendingPlatformSubscriptionRepository->save($pendingSubscription);

            $this->logger->info('Pending platform subscription cancelled', [
                'subscription_id' => $subscriptionId,
            ]);
            return;
        }

        // Otherwise, update user subscription
        $user = $this->userRepository->findBySubscriptionId($subscriptionId);

        if ($user === null) {
            $this->logger->error('Subscription not found (neither pending nor redeemed)', [
                'subscription_id' => $subscriptionId
            ]);
            return;
        }

        $user->setPlatformSubscription($platformSubscription);
        $this->userRepository->save($user);

        $this->logger->info('Platform subscription deleted for user', [
            'user_id' => $user->id()->value(),
            'subscription_id' => $subscriptionId,
        ]);
    }

    private function extractPlanId(array $data): string
    {
        return $data['items']['data'][0]['price']['id'] ?? 'unknown';
    }

    private function createPlatformSubscriptionFromData(array $data): PlatformSubscription
    {
        $planId = $this->extractPlanId($data);

        // Handle expiration date - try different fields that Stripe might send
        $expiresAt = null;
        if (isset($data['current_period_end'])) {
            $expiresAt = new \DateTimeImmutable('@' . $data['current_period_end']);
        } elseif (isset($data['cancel_at'])) {
            $expiresAt = new \DateTimeImmutable('@' . $data['cancel_at']);
        } elseif (isset($data['ended_at'])) {
            $expiresAt = new \DateTimeImmutable('@' . $data['ended_at']);
        } else {
            // Default to 1 year from now for subscriptions without explicit end date
            $expiresAt = new \DateTimeImmutable('+1 year');
            $this->logger->warning('No expiration date found in subscription data, defaulting to 1 year', [
                'subscription_id' => $data['id'] ?? 'unknown',
            ]);
        }

        return new PlatformSubscription(
            subscriptionId: $data['id'],
            planId: $planId,
            status: $data['status'],
            expiresAt: $expiresAt,
        );
    }
}
