<?php
declare(strict_types=1);

namespace Tests\Unit\App\Admin\Domain\User;

use App\Admin\Domain\User\PendingPlatformSubscription;
use App\Admin\Domain\User\PendingPlatformSubscriptionId;
use App\Admin\Domain\User\PlatformSubscription;
use App\Admin\Domain\User\PlatformSubscriptionCouponGenerated;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PendingPlatformSubscription::class)]
final class PendingPlatformSubscriptionTest extends TestCase
{
    public function testCreatesWithCouponCodeAndRecordsEvent(): void
    {
        // Arrange
        $subscriptionData = new PlatformSubscription(
            subscriptionId: 'sub_platform_123',
            planId: 'price_platform_monthly',
            status: 'active',
            expiresAt: new \DateTimeImmutable('+30 days'),
        );
        $customerEmail = 'serverowner@example.com';

        // Act
        $pending = PendingPlatformSubscription::create(
            id: PendingPlatformSubscriptionId::generate(),
            customerEmail: $customerEmail,
            subscription: $subscriptionData,
        );

        // Assert
        $this->assertEquals($customerEmail, $pending->getCustomerEmail());
        $this->assertEquals($subscriptionData, $pending->getSubscription());
        $this->assertNotEmpty($pending->getCouponCode());
        $this->assertFalse($pending->isRedeemed());

        // Assert event was recorded
        $events = $pending->pullEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(PlatformSubscriptionCouponGenerated::class, $events[0]);

        $event = $events[0];
        $this->assertEquals($customerEmail, $event->customerEmail);
        $this->assertEquals($pending->getCouponCode(), $event->couponCode);
    }

    public function testCanBeMarkedAsRedeemed(): void
    {
        // Arrange
        $pending = PendingPlatformSubscription::create(
            id: PendingPlatformSubscriptionId::generate(),
            customerEmail: 'serverowner@example.com',
            subscription: new PlatformSubscription(
                subscriptionId: 'sub_platform_123',
                planId: 'price_platform_monthly',
                status: 'active',
                expiresAt: new \DateTimeImmutable('+30 days'),
            ),
        );

        // Act
        $pending->markAsRedeemed();

        // Assert
        $this->assertTrue($pending->isRedeemed());
    }

    public function testCouponCodeIsUnique(): void
    {
        // Arrange & Act
        $pending1 = PendingPlatformSubscription::create(
            id: PendingPlatformSubscriptionId::generate(),
            customerEmail: 'owner1@example.com',
            subscription: new PlatformSubscription(
                subscriptionId: 'sub_1',
                planId: 'price_1',
                status: 'active',
                expiresAt: new \DateTimeImmutable('+30 days'),
            ),
        );

        $pending2 = PendingPlatformSubscription::create(
            id: PendingPlatformSubscriptionId::generate(),
            customerEmail: 'owner2@example.com',
            subscription: new PlatformSubscription(
                subscriptionId: 'sub_2',
                planId: 'price_2',
                status: 'active',
                expiresAt: new \DateTimeImmutable('+30 days'),
            ),
        );

        // Assert
        $this->assertNotEquals($pending1->getCouponCode(), $pending2->getCouponCode());
    }
}
