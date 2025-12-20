<?php
declare(strict_types=1);

namespace Tests\Unit\App\Admin\Application\PlatformSubscription;

use App\Admin\Application\PlatformSubscription\RedeemPlatformSubscriptionCouponUseCase;
use App\Admin\Domain\User\PendingPlatformSubscription;
use App\Admin\Domain\User\PendingPlatformSubscriptionId;
use App\Admin\Domain\User\PlatformSubscription;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Doubles\App\Admin\Domain\User\UserMother;
use Tests\Doubles\App\Admin\Infrastructure\Persistence\InMemory\User\InMemoryPendingPlatformSubscriptionRepository;
use Tests\Doubles\App\Admin\Infrastructure\Persistence\InMemory\User\InMemoryUserRepository;

#[CoversClass(RedeemPlatformSubscriptionCouponUseCase::class)]
final class RedeemPlatformSubscriptionCouponUseCaseTest extends TestCase
{
    private InMemoryUserRepository $userRepository;
    private InMemoryPendingPlatformSubscriptionRepository $pendingRepository;
    private RedeemPlatformSubscriptionCouponUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = new InMemoryUserRepository();
        $this->pendingRepository = new InMemoryPendingPlatformSubscriptionRepository();
        $this->useCase = new RedeemPlatformSubscriptionCouponUseCase(
            $this->userRepository,
            $this->pendingRepository,
        );
    }

    public function testRedeemsCouponAndLinksSubscriptionToUser(): void
    {
        // Arrange: User exists without subscription
        $user = UserMother::createWithPlatformSubscription(null);
        $this->userRepository->save($user);

        // Pending subscription exists with coupon
        $subscription = new PlatformSubscription(
            subscriptionId: 'sub_platform_123',
            planId: 'price_platform_monthly',
            status: 'active',
            expiresAt: new \DateTimeImmutable('+30 days'),
        );

        $pending = PendingPlatformSubscription::create(
            id: PendingPlatformSubscriptionId::generate(),
            customerEmail: 'owner@example.com',
            subscription: $subscription,
        );
        $couponCode = $pending->getCouponCode();
        $this->pendingRepository->save($pending);

        // Act
        ($this->useCase)($user->id(), $couponCode);

        // Assert: User now has the subscription
        $updatedUser = $this->userRepository->findByUserId($user->id());
        $this->assertTrue($updatedUser->hasActivePlatformSubscription());
        $this->assertEquals('sub_platform_123', $updatedUser->getPlatformSubscription()->getSubscriptionId());

        // Assert: Pending subscription is marked as redeemed
        $updatedPending = $this->pendingRepository->findByCouponCode($couponCode);
        $this->assertTrue($updatedPending->isRedeemed());
    }

    public function testThrowsExceptionWhenCouponNotFound(): void
    {
        // Arrange: User exists
        $user = UserMother::createWithPlatformSubscription(null);
        $this->userRepository->save($user);

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid coupon code');

        ($this->useCase)($user->id(), 'INVALID-COUPON');
    }

    public function testThrowsExceptionWhenCouponAlreadyRedeemed(): void
    {
        // Arrange: User exists
        $user = UserMother::createWithPlatformSubscription(null);
        $this->userRepository->save($user);

        // Pending subscription already redeemed
        $subscription = new PlatformSubscription(
            subscriptionId: 'sub_platform_123',
            planId: 'price_platform_monthly',
            status: 'active',
            expiresAt: new \DateTimeImmutable('+30 days'),
        );

        $pending = PendingPlatformSubscription::create(
            id: PendingPlatformSubscriptionId::generate(),
            customerEmail: 'owner@example.com',
            subscription: $subscription,
        );
        $couponCode = $pending->getCouponCode();
        $pending->markAsRedeemed();
        $this->pendingRepository->save($pending);

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Coupon already redeemed');

        ($this->useCase)($user->id(), $couponCode);
    }

    public function testThrowsExceptionWhenUserNotFound(): void
    {
        // Arrange: Pending subscription exists
        $subscription = new PlatformSubscription(
            subscriptionId: 'sub_platform_123',
            planId: 'price_platform_monthly',
            status: 'active',
            expiresAt: new \DateTimeImmutable('+30 days'),
        );

        $pending = PendingPlatformSubscription::create(
            id: PendingPlatformSubscriptionId::generate(),
            customerEmail: 'owner@example.com',
            subscription: $subscription,
        );
        $couponCode = $pending->getCouponCode();
        $this->pendingRepository->save($pending);

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('User not found');

        // Non-existent user ID
        ($this->useCase)(UserMother::createWithPlatformSubscription(null)->id(), $couponCode);
    }
}
