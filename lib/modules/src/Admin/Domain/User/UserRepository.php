<?php
declare(strict_types=1);

namespace App\Admin\Domain\User;

use App\Frontend\Domain\Discord\DiscordId;

interface UserRepository
{
    public function findOneByDiscordId(DiscordId $discordId): ?User;
    public function findByUserId(UserId $userId): ?User;
    public function findByEmail(string $email): ?User;
    public function findByUsername(string $username): ?User;
    public function findBySubscriptionId(string $subscriptionId): ?User;
    public function save(User $user): void;
}
