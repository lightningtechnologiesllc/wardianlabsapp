<?php
declare(strict_types=1);

namespace Tests\Doubles\App\Admin\Infrastructure\Persistence\InMemory\User;

use App\Admin\Domain\User\User;
use App\Admin\Domain\User\UserId;
use App\Admin\Domain\User\UserRepository;
use App\Frontend\Domain\Discord\DiscordId;

final class InMemoryUserRepository implements UserRepository
{
    private array $users = [];

    public function findOneByDiscordId(DiscordId $discordId): ?User
    {
        return array_find($this->users, fn($user) => $user->getDiscordId()->equals($discordId));
    }

    public function findByUserId(UserId $userId): ?User
    {
        return array_find($this->users, fn($user) => $user->id()->equals($userId));
    }

    public function findByEmail(string $email): ?User
    {
        return array_find($this->users, fn($user) => $user->getEmail() === $email);
    }

    public function findByUsername(string $username): ?User
    {
        return array_find($this->users, fn($user) => $user->getUsername() === $username);
    }

    public function findBySubscriptionId(string $subscriptionId): ?User
    {
        return array_find($this->users, fn($user) =>
            $user->getPlatformSubscription()?->getSubscriptionId() === $subscriptionId
        );
    }

    public function save(User $user): void
    {
        /** @var User $existingUser */
        foreach ($this->users as $key => $existingUser) {
            if ($existingUser->id()->equals($user->id())) {
                $this->users[$key] = $user;
                return;
            }
        }

        $this->users[] = $user;
    }
}
