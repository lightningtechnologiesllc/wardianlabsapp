<?php
declare(strict_types=1);

namespace App\Admin\Application\PlatformSubscription;

use App\Admin\Domain\User\PendingPlatformSubscriptionRepository;
use App\Admin\Domain\User\UserId;
use App\Admin\Domain\User\UserRepository;
use InvalidArgumentException;

final readonly class RedeemPlatformSubscriptionCouponUseCase
{
    public function __construct(
        private UserRepository $userRepository,
        private PendingPlatformSubscriptionRepository $pendingRepository,
    ) {
    }

    public function __invoke(UserId $userId, string $couponCode): void
    {
        // Find the pending subscription by coupon code
        $pendingSubscription = $this->pendingRepository->findByCouponCode($couponCode);

        if ($pendingSubscription === null) {
            throw new InvalidArgumentException('Invalid coupon code');
        }

        if ($pendingSubscription->isRedeemed()) {
            throw new InvalidArgumentException('Coupon already redeemed');
        }

        // Find the user
        $user = $this->userRepository->findByUserId($userId);

        if ($user === null) {
            throw new InvalidArgumentException('User not found');
        }

        // Link subscription to user
        $user->setPlatformSubscription($pendingSubscription->getSubscription());
        $this->userRepository->save($user);

        // Mark pending subscription as redeemed
        $pendingSubscription->markAsRedeemed();
        $this->pendingRepository->save($pendingSubscription);
    }
}
