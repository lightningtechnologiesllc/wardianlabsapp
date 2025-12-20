<?php
declare(strict_types=1);

namespace App\Admin\Application\Stripe\MessageHandler;

use App\Admin\Application\Stripe\Message\ProcessSubscriptionCreatedMessage;
use App\Admin\Domain\Stripe\PlatformStripeProvider;
use App\Admin\Domain\User\PendingPlatformSubscription;
use App\Admin\Domain\User\PendingPlatformSubscriptionId;
use App\Admin\Domain\User\PendingPlatformSubscriptionRepository;
use App\Admin\Domain\User\PlatformSubscription;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final readonly class ProcessSubscriptionCreatedHandler
{
    public function __construct(
        private PendingPlatformSubscriptionRepository $pendingPlatformSubscriptionRepository,
        private MessageBusInterface $eventBus,
        private PlatformStripeProvider $platformStripeProvider,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(ProcessSubscriptionCreatedMessage $message): void
    {
        $subscriptionData = $message->subscriptionData;
        $customerId = $subscriptionData['customer'];

        $this->logger->info('Processing subscription created message', [
            'subscription_id' => $subscriptionData['id'],
            'customer_id' => $customerId,
        ]);

        $customerEmail = $this->platformStripeProvider->getCustomerEmail($customerId);

        $platformSubscription = $this->createPlatformSubscriptionFromData($subscriptionData);

        // Create pending subscription with coupon code
        $pendingSubscription = PendingPlatformSubscription::create(
            id: PendingPlatformSubscriptionId::generate(),
            customerEmail: $customerEmail,
            subscription: $platformSubscription,
        );

        $this->pendingPlatformSubscriptionRepository->save($pendingSubscription);

        // Dispatch events recorded by the aggregate
        foreach ($pendingSubscription->pullEvents() as $domainEvent) {
            $this->eventBus->dispatch($domainEvent);
        }

        $this->logger->info('Pending platform subscription created with coupon', [
            'email' => $customerEmail,
            'subscription_id' => $platformSubscription->getSubscriptionId(),
            'coupon_code' => $pendingSubscription->getCouponCode(),
        ]);
    }

    private function createPlatformSubscriptionFromData(array $data): PlatformSubscription
    {
        $planId = $data['items']['data'][0]['price']['id'] ?? 'unknown';

        // Handle expiration date - try different fields that Stripe might send
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
