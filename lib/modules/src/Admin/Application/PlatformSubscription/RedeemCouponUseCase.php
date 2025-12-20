<?php
declare(strict_types=1);

namespace App\Admin\Application\PlatformSubscription;

use App\Admin\Domain\User\PendingPlatformSubscriptionRepository;
use App\Admin\Domain\User\User;
use App\Admin\Domain\User\UserRepository;

final readonly class RedeemCouponUseCase
{
    public function __construct(
        private PendingPlatformSubscriptionRepository $pendingRepository,
        private UserRepository $userRepository,
    ) {
    }

    public function __invoke(User $user, string $couponCode): void
    {
        $pendingSubscription = $this->pendingRepository->findByCouponCode($couponCode);

        if ($pendingSubscription === null) {
            throw new InvalidCouponException('Invalid coupon code');
        }

        if ($pendingSubscription->isRedeemed()) {
            throw new InvalidCouponException('This coupon has already been redeemed');
        }

        if (!$pendingSubscription->getSubscription()->isActive()) {
            throw new InvalidCouponException('This subscription is no longer active');
        }

        $pendingSubscription->markAsRedeemed();
        $this->pendingRepository->save($pendingSubscription);

        $user->setPlatformSubscription($pendingSubscription->getSubscription());
        $this->userRepository->save($user);
    }
}
