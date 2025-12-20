<?php
declare(strict_types=1);

namespace Tests\Unit\App\Admin\Application\Stripe;

use App\Admin\Application\Stripe\HandlePlatformSubscriptionWebhookUseCase;
use App\Admin\Application\Stripe\Message\ProcessSubscriptionCreatedMessage;
use App\Admin\Domain\User\AllowedPlatformPrices;
use App\Admin\Domain\User\PendingPlatformSubscription;
use App\Admin\Domain\User\PendingPlatformSubscriptionId;
use App\Admin\Domain\User\PlatformSubscription;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Tests\Doubles\App\Admin\Domain\User\UserMother;
use Tests\Doubles\App\Admin\Infrastructure\Persistence\InMemory\User\InMemoryPendingPlatformSubscriptionRepository;
use Tests\Doubles\App\Admin\Infrastructure\Persistence\InMemory\User\InMemoryUserRepository;

#[CoversClass(HandlePlatformSubscriptionWebhookUseCase::class)]
final class HandlePlatformSubscriptionWebhookUseCaseTest extends TestCase
{
    private InMemoryUserRepository $userRepository;
    private InMemoryPendingPlatformSubscriptionRepository $pendingRepository;
    private object $messageBus;
    private AllowedPlatformPrices $allowedPlatformPrices;
    private HandlePlatformSubscriptionWebhookUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = new InMemoryUserRepository();
        $this->pendingRepository = new InMemoryPendingPlatformSubscriptionRepository();
        $this->messageBus = new class implements MessageBusInterface {
            public array $dispatched = [];

            public function dispatch(object $message, array $stamps = []): Envelope
            {
                $this->dispatched[] = $message;
                return new Envelope($message, $stamps);
            }
        };
        $this->allowedPlatformPrices = new AllowedPlatformPrices('price_platform_monthly,free');
        $this->useCase = new HandlePlatformSubscriptionWebhookUseCase(
            $this->userRepository,
            $this->pendingRepository,
            $this->messageBus,
            new NullLogger(),
            $this->allowedPlatformPrices,
        );
    }

    public function testSubscriptionCreatedDispatchesToQueue(): void
    {
        $event = [
            'type' => 'customer.subscription.created',
            'data' => [
                'object' => [
                    'id' => 'sub_platform_123',
                    'items' => ['data' => [['price' => ['id' => 'price_platform_monthly']]]],
                    'status' => 'active',
                    'current_period_end' => time() + (30 * 24 * 60 * 60),
                ],
            ],
        ];

        ($this->useCase)($event);

        $this->assertCount(1, $this->messageBus->dispatched);
        $this->assertInstanceOf(ProcessSubscriptionCreatedMessage::class, $this->messageBus->dispatched[0]);
    }

    public function testSubscriptionCreatedWithInvalidPriceIsIgnored(): void
    {
        $event = [
            'type' => 'customer.subscription.created',
            'data' => [
                'object' => [
                    'id' => 'sub_platform_123',
                    'items' => ['data' => [['price' => ['id' => 'price_invalid']]]],
                    'status' => 'active',
                    'current_period_end' => time() + (30 * 24 * 60 * 60),
                ],
            ],
        ];

        ($this->useCase)($event);

        $this->assertEmpty($this->messageBus->dispatched);
    }

    public function testSubscriptionUpdatedUpdatesPendingIfNotRedeemed(): void
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
        $this->pendingRepository->save($pending);

        // Act: Subscription updated to past_due
        $event = [
            'type' => 'customer.subscription.updated',
            'data' => [
                'object' => [
                    'id' => 'sub_platform_123',
                    'items' => ['data' => [['price' => ['id' => 'price_platform_monthly']]]],
                    'status' => 'past_due',
                    'current_period_end' => time() - (1 * 24 * 60 * 60),
                ],
            ],
        ];

        ($this->useCase)($event);

        // Assert
        $updatedPending = $this->pendingRepository->findBySubscriptionId('sub_platform_123');
        $this->assertNotNull($updatedPending);
        $this->assertEquals('past_due', $updatedPending->getSubscription()->getStatus());
    }

    public function testSubscriptionUpdatedWithInvalidPriceIsIgnored(): void
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
        $this->pendingRepository->save($pending);

        // Act: Update event with invalid price
        $event = [
            'type' => 'customer.subscription.updated',
            'data' => [
                'object' => [
                    'id' => 'sub_platform_123',
                    'items' => ['data' => [['price' => ['id' => 'price_invalid']]]],
                    'status' => 'past_due',
                    'current_period_end' => time() - (1 * 24 * 60 * 60),
                ],
            ],
        ];

        ($this->useCase)($event);

        // Assert: Pending subscription was NOT updated (still active)
        $updatedPending = $this->pendingRepository->findBySubscriptionId('sub_platform_123');
        $this->assertEquals('active', $updatedPending->getSubscription()->getStatus());
    }

    public function testSubscriptionUpdatedUpdatesUserIfAlreadyRedeemed(): void
    {
        // Arrange: User has redeemed subscription
        $activeSubscription = new PlatformSubscription(
            subscriptionId: 'sub_platform_123',
            planId: 'price_platform_monthly',
            status: 'active',
            expiresAt: new \DateTimeImmutable('+30 days')
        );
        $user = UserMother::createWithPlatformSubscription($activeSubscription);
        $this->userRepository->save($user);

        $event = [
            'type' => 'customer.subscription.updated',
            'data' => [
                'object' => [
                    'id' => 'sub_platform_123',
                    'items' => ['data' => [['price' => ['id' => 'price_platform_monthly']]]],
                    'status' => 'past_due',
                    'current_period_end' => time() - (1 * 24 * 60 * 60),
                ],
            ],
        ];

        ($this->useCase)($event);

        $updatedUser = $this->userRepository->findBySubscriptionId('sub_platform_123');
        $this->assertNotNull($updatedUser);
        $this->assertFalse($updatedUser->hasActivePlatformSubscription());
        $this->assertEquals('past_due', $updatedUser->getPlatformSubscription()->getStatus());
    }

    public function testSubscriptionDeletedUpdatesPendingIfNotRedeemed(): void
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
        $this->pendingRepository->save($pending);

        $event = [
            'type' => 'customer.subscription.deleted',
            'data' => [
                'object' => [
                    'id' => 'sub_platform_123',
                    'items' => ['data' => [['price' => ['id' => 'price_platform_monthly']]]],
                    'status' => 'canceled',
                    'current_period_end' => time(),
                ],
            ],
        ];

        ($this->useCase)($event);

        $updatedPending = $this->pendingRepository->findBySubscriptionId('sub_platform_123');
        $this->assertNotNull($updatedPending);
        $this->assertEquals('canceled', $updatedPending->getSubscription()->getStatus());
    }

    public function testSubscriptionDeletedUpdatesUserIfAlreadyRedeemed(): void
    {
        // Arrange: User has redeemed subscription
        $activeSubscription = new PlatformSubscription(
            subscriptionId: 'sub_platform_123',
            planId: 'price_platform_monthly',
            status: 'active',
            expiresAt: new \DateTimeImmutable('+30 days')
        );
        $user = UserMother::createWithPlatformSubscription($activeSubscription);
        $this->userRepository->save($user);

        $event = [
            'type' => 'customer.subscription.deleted',
            'data' => [
                'object' => [
                    'id' => 'sub_platform_123',
                    'items' => ['data' => [['price' => ['id' => 'price_platform_monthly']]]],
                    'status' => 'canceled',
                    'current_period_end' => time(),
                ],
            ],
        ];

        ($this->useCase)($event);

        $updatedUser = $this->userRepository->findBySubscriptionId('sub_platform_123');
        $this->assertNotNull($updatedUser);
        $this->assertFalse($updatedUser->hasActivePlatformSubscription());
        $this->assertEquals('canceled', $updatedUser->getPlatformSubscription()->getStatus());
    }

    public function testSubscriptionDeletedWithInvalidPriceIsIgnored(): void
    {
        // Arrange: User has redeemed subscription
        $activeSubscription = new PlatformSubscription(
            subscriptionId: 'sub_platform_123',
            planId: 'price_platform_monthly',
            status: 'active',
            expiresAt: new \DateTimeImmutable('+30 days')
        );
        $user = UserMother::createWithPlatformSubscription($activeSubscription);
        $this->userRepository->save($user);

        $event = [
            'type' => 'customer.subscription.deleted',
            'data' => [
                'object' => [
                    'id' => 'sub_platform_123',
                    'items' => ['data' => [['price' => ['id' => 'price_invalid']]]],
                    'status' => 'canceled',
                    'current_period_end' => time(),
                ],
            ],
        ];

        ($this->useCase)($event);

        // Assert: User subscription was NOT updated (still active)
        $updatedUser = $this->userRepository->findBySubscriptionId('sub_platform_123');
        $this->assertEquals('active', $updatedUser->getPlatformSubscription()->getStatus());
    }
}
