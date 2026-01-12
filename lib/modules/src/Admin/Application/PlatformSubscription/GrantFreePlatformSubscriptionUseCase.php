<?php
declare(strict_types=1);

namespace App\Admin\Application\PlatformSubscription;

use App\Admin\Domain\User\PlatformSubscription;
use App\Admin\Domain\User\UserRepository;

final class GrantFreePlatformSubscriptionUseCase
{
    private const FREE_SUBSCRIPTION_ID = 'free_manual';
    private const FREE_PLAN_ID = 'free';
    private const ACTIVE_STATUS = 'active';

    public function __construct(
        private UserRepository $userRepository,
    ) {
    }

    public function __invoke(string $username): void
    {
        $user = $this->userRepository->findByUsername($username);

        if ($user === null) {
            throw new \RuntimeException("User not found with username: $username");
        }

        $freeSubscription = new PlatformSubscription(
            subscriptionId: self::FREE_SUBSCRIPTION_ID,
            planId: self::FREE_PLAN_ID,
            status: self::ACTIVE_STATUS,
            expiresAt: new \DateTimeImmutable('+100 years'),
        );

        $user->setPlatformSubscription($freeSubscription);
        $this->userRepository->save($user);
    }
}
