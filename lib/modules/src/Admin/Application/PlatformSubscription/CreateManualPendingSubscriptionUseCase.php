<?php
declare(strict_types=1);

namespace App\Admin\Application\PlatformSubscription;

use App\Admin\Domain\User\PendingPlatformSubscription;
use App\Admin\Domain\User\PendingPlatformSubscriptionId;
use App\Admin\Domain\User\PendingPlatformSubscriptionRepository;
use App\Admin\Domain\User\PlatformSubscription;

final readonly class CreateManualPendingSubscriptionUseCase
{
    public function __construct(
        private PendingPlatformSubscriptionRepository $pendingRepository,
    ) {
    }

    public function __invoke(
        string $customerEmail,
        string $subscriptionId,
        string $planId,
        \DateTimeImmutable $expiresAt,
    ): PendingPlatformSubscription {
        $existing = $this->pendingRepository->findBySubscriptionId($subscriptionId);
        if ($existing !== null) {
            throw new \RuntimeException(sprintf(
                'A pending subscription with ID "%s" already exists',
                $subscriptionId
            ));
        }

        $platformSubscription = new PlatformSubscription(
            subscriptionId: $subscriptionId,
            planId: $planId,
            status: 'active',
            expiresAt: $expiresAt,
        );

        $pendingSubscription = PendingPlatformSubscription::create(
            id: PendingPlatformSubscriptionId::generate(),
            customerEmail: $customerEmail,
            subscription: $platformSubscription,
        );

        $this->pendingRepository->save($pendingSubscription);

        return $pendingSubscription;
    }
}
