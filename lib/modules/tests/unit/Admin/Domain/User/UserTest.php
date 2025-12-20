<?php
declare(strict_types=1);

namespace Tests\Unit\App\Admin\Domain\User;

use App\Admin\Domain\User\PlatformSubscription;
use App\Admin\Domain\User\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Doubles\App\Admin\Domain\User\UserMother;

#[CoversClass(User::class)]
final class UserTest extends TestCase
{
    public function testHasActivePlatformSubscriptionReturnsTrueWhenSubscriptionIsActive(): void
    {
        // Arrange
        $subscription = new PlatformSubscription(
            subscriptionId: 'sub_platform_123',
            planId: 'price_platform_monthly',
            status: 'active',
            expiresAt: new \DateTimeImmutable('+30 days')
        );
        $user = UserMother::createWithPlatformSubscription($subscription);

        // Act & Assert
        $this->assertTrue($user->hasActivePlatformSubscription());
    }

    public function testHasActivePlatformSubscriptionReturnsFalseWhenSubscriptionIsCanceled(): void
    {
        // Arrange
        $subscription = new PlatformSubscription(
            subscriptionId: 'sub_platform_123',
            planId: 'price_platform_monthly',
            status: 'canceled',
            expiresAt: new \DateTimeImmutable('-1 day')
        );
        $user = UserMother::createWithPlatformSubscription($subscription);

        // Act & Assert
        $this->assertFalse($user->hasActivePlatformSubscription());
    }

    public function testHasActivePlatformSubscriptionReturnsFalseWhenNoSubscription(): void
    {
        // Arrange
        $user = UserMother::createWithPlatformSubscription(null);

        // Act & Assert
        $this->assertFalse($user->hasActivePlatformSubscription());
    }

    public function testHasActivePlatformSubscriptionReturnsFalseWhenSubscriptionIsPastDue(): void
    {
        // Arrange
        $subscription = new PlatformSubscription(
            subscriptionId: 'sub_platform_123',
            planId: 'price_platform_monthly',
            status: 'past_due',
            expiresAt: new \DateTimeImmutable('-5 days')
        );
        $user = UserMother::createWithPlatformSubscription($subscription);

        // Act & Assert
        $this->assertFalse($user->hasActivePlatformSubscription());
    }

    public function testHasActivePlatformSubscriptionReturnsFalseWhenSubscriptionHasExpired(): void
    {
        // Arrange - subscription marked as active but already expired
        $subscription = new PlatformSubscription(
            subscriptionId: 'sub_platform_123',
            planId: 'price_platform_monthly',
            status: 'active',
            expiresAt: new \DateTimeImmutable('-1 day')
        );
        $user = UserMother::createWithPlatformSubscription($subscription);

        // Act & Assert
        $this->assertFalse($user->hasActivePlatformSubscription(), 'Expired subscription should not grant access even if status is active');
    }

    public function testCanSetPlatformSubscription(): void
    {
        // Arrange
        $user = UserMother::createWithPlatformSubscription(null);
        $subscription = new PlatformSubscription(
            subscriptionId: 'sub_new_123',
            planId: 'price_platform_monthly',
            status: 'active',
            expiresAt: new \DateTimeImmutable('+30 days')
        );

        // Act
        $user->setPlatformSubscription($subscription);

        // Assert
        $this->assertTrue($user->hasActivePlatformSubscription());
        $this->assertEquals('sub_new_123', $user->getPlatformSubscription()->getSubscriptionId());
        $this->assertEquals('active', $user->getPlatformSubscription()->getStatus());
    }

    public function testCanCancelPlatformSubscription(): void
    {
        // Arrange
        $activeSubscription = new PlatformSubscription(
            subscriptionId: 'sub_123',
            planId: 'price_platform_monthly',
            status: 'active',
            expiresAt: new \DateTimeImmutable('+30 days')
        );
        $user = UserMother::createWithPlatformSubscription($activeSubscription);

        $canceledSubscription = new PlatformSubscription(
            subscriptionId: 'sub_123',
            planId: 'price_platform_monthly',
            status: 'canceled',
            expiresAt: new \DateTimeImmutable()
        );

        // Act
        $user->setPlatformSubscription($canceledSubscription);

        // Assert
        $this->assertFalse($user->hasActivePlatformSubscription());
        $this->assertEquals('canceled', $user->getPlatformSubscription()->getStatus());
    }
}
