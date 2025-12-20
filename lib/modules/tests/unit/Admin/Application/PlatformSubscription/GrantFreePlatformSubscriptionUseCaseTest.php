<?php
declare(strict_types=1);

namespace Tests\Unit\App\Admin\Application\PlatformSubscription;

use App\Admin\Application\PlatformSubscription\GrantFreePlatformSubscriptionUseCase;
use App\Admin\Domain\User\PlatformSubscription;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Doubles\App\Admin\Domain\User\UserMother;
use Tests\Doubles\App\Admin\Infrastructure\Persistence\InMemory\User\InMemoryUserRepository;

#[CoversClass(GrantFreePlatformSubscriptionUseCase::class)]
final class GrantFreePlatformSubscriptionUseCaseTest extends TestCase
{
    private InMemoryUserRepository $userRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = new InMemoryUserRepository();
    }

    public function testGrantsFreePlatformSubscriptionToExistingUser(): void
    {
        // Arrange
        $username = 'testuser123';
        $user = UserMother::randomWithUsername($username);
        $this->userRepository->save($user);

        // Act
        $useCase = new GrantFreePlatformSubscriptionUseCase($this->userRepository);
        ($useCase)($username);

        // Assert
        $updatedUser = $this->userRepository->findByUsername($username);
        $this->assertNotNull($updatedUser);
        $this->assertTrue($updatedUser->hasActivePlatformSubscription());

        $subscription = $updatedUser->getPlatformSubscription();
        $this->assertEquals('free_manual', $subscription->getSubscriptionId());
        $this->assertEquals('free', $subscription->getPlanId());
        $this->assertEquals('active', $subscription->getStatus());
        $this->assertTrue($subscription->isActive());
    }

    public function testThrowsExceptionWhenUserNotFound(): void
    {
        $useCase = new GrantFreePlatformSubscriptionUseCase($this->userRepository);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('User not found with username: nonexistent');

        ($useCase)('nonexistent');
    }

    public function testOverwritesExistingSubscription(): void
    {
        // Arrange: User with existing subscription
        $username = 'testuser123';
        $existingSubscription = new PlatformSubscription(
            subscriptionId: 'sub_existing',
            planId: 'price_existing',
            status: 'active',
            expiresAt: new \DateTimeImmutable('+30 days'),
        );
        $user = UserMother::createWithPlatformSubscription($existingSubscription);
        $user->setUsername($username);
        $this->userRepository->save($user);

        // Act
        $useCase = new GrantFreePlatformSubscriptionUseCase($this->userRepository);
        ($useCase)($username);

        // Assert
        $updatedUser = $this->userRepository->findByUsername($username);
        $subscription = $updatedUser->getPlatformSubscription();
        $this->assertEquals('free_manual', $subscription->getSubscriptionId());
    }
}
