<?php
declare(strict_types=1);

namespace Tests\Unit\App\Admin\Domain\User;

use App\Admin\Domain\User\PlatformSubscription;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PlatformSubscription::class)]
final class PlatformSubscriptionTest extends TestCase
{
    public function testIsActiveReturnsTrueWhenStatusIsActiveAndNotExpired(): void
    {
        // Arrange
        $subscription = new PlatformSubscription(
            subscriptionId: 'sub_123',
            planId: 'price_platform_monthly',
            status: 'active',
            expiresAt: new \DateTimeImmutable('+30 days')
        );

        // Act & Assert
        $this->assertTrue($subscription->isActive());
    }

    public function testIsActiveReturnsFalseWhenStatusIsCanceled(): void
    {
        // Arrange
        $subscription = new PlatformSubscription(
            subscriptionId: 'sub_123',
            planId: 'price_platform_monthly',
            status: 'canceled',
            expiresAt: new \DateTimeImmutable('+30 days')
        );

        // Act & Assert
        $this->assertFalse($subscription->isActive());
    }

    public function testIsActiveReturnsFalseWhenExpired(): void
    {
        // Arrange
        $subscription = new PlatformSubscription(
            subscriptionId: 'sub_123',
            planId: 'price_platform_monthly',
            status: 'active',
            expiresAt: new \DateTimeImmutable('-1 day')
        );

        // Act & Assert
        $this->assertFalse($subscription->isActive());
    }

    public function testIsActiveReturnsFalseWhenStatusIsPastDue(): void
    {
        // Arrange
        $subscription = new PlatformSubscription(
            subscriptionId: 'sub_123',
            planId: 'price_platform_monthly',
            status: 'past_due',
            expiresAt: new \DateTimeImmutable('+30 days')
        );

        // Act & Assert
        $this->assertFalse($subscription->isActive());
    }

    public function testCanConvertToArray(): void
    {
        // Arrange
        $expiresAt = new \DateTimeImmutable('2025-12-31 23:59:59');
        $subscription = new PlatformSubscription(
            subscriptionId: 'sub_123',
            planId: 'price_platform_monthly',
            status: 'active',
            expiresAt: $expiresAt
        );

        // Act
        $array = $subscription->toArray();

        // Assert
        $this->assertEquals('sub_123', $array['subscription_id']);
        $this->assertEquals('price_platform_monthly', $array['plan_id']);
        $this->assertEquals('active', $array['status']);
        $this->assertEquals('2025-12-31 23:59:59', $array['expires_at']);
    }

    public function testCanCreateFromArray(): void
    {
        // Arrange
        $data = [
            'subscription_id' => 'sub_123',
            'plan_id' => 'price_platform_monthly',
            'status' => 'active',
            'expires_at' => new \DateTimeImmutable('+30 days')->format('Y-m-d H:i:s'),
        ];

        // Act
        $subscription = PlatformSubscription::fromArray($data);

        // Assert
        $this->assertTrue($subscription->isActive());
        $this->assertEquals('sub_123', $subscription->getSubscriptionId());
        $this->assertEquals('price_platform_monthly', $subscription->getPlanId());
        $this->assertEquals('active', $subscription->getStatus());
    }
}
