<?php
declare(strict_types=1);

namespace App\Admin\Domain\User;

use App\Admin\Domain\Tenant\Tenant;
use App\Admin\Domain\Tenant\Tenants;
use App\Core\Types\Aggregate\AggregateRoot;
use App\Frontend\Domain\Discord\DiscordId;
use App\Shared\Domain\Discord\DiscordAccessToken;
use Symfony\Component\Security\Core\User\UserInterface;

final class User extends AggregateRoot implements UserInterface
{
    public function __construct(
        private readonly UserId             $userId,
        private readonly DiscordId          $discordId,
        private string             $username,
        private string             $globalName,
        private string             $email,
        private string             $avatar,
        private DiscordAccessToken $accessToken,
        private Tenants $tenants = new Tenants([]),
        private ?PlatformSubscription $platformSubscription = null,
    )
    {
    }

    public static function fromArray(array $userData): self
    {
        return new self(
            new UserId($userData['user_id']),
            new DiscordId($userData['discord_id']),
            $userData['username'],
            $userData['global_name'] ?? '',
            $userData['email'] ?? '',
            $userData['avatar'] ?? '',
            DiscordAccessToken::fromArray($userData['access_token']),
            Tenants::fromArray($userData['tenants'] ?? []),
            isset($userData['platform_subscription']) ? PlatformSubscription::fromArray($userData['platform_subscription']) : null,
        );
    }

    public function id(): UserId
    {
        return $this->userId;
    }

    public function getDiscordId(): DiscordId
    {
        return $this->discordId;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getGlobalName(): string
    {
        return $this->globalName;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getAvatar(): string
    {
        return $this->avatar;
    }

    public function getAccessToken(): DiscordAccessToken
    {
        return $this->accessToken;
    }

    public function setAccessToken(DiscordAccessToken $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    public function getTenants(): Tenants
    {
        return $this->tenants;
    }

    public function getRoles(): array
    {
        return ['ROLE_ADMIN'];
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId->value(),
            'discord_id' => $this->discordId->value(),
            'username' => $this->username,
            'global_name' => $this->globalName,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'access_token' => $this->accessToken->toArray(),
            'tenants' => $this->tenants->toArray(),
            'platform_subscription' => $this->platformSubscription?->toArray(),
        ];
    }

    public function addTenant(Tenant $tenant): void
    {
        $this->tenants->add($tenant);
        $tenant->setOwner($this);
    }

    public function setAvatar(string $avatar): void
    {
        $this->avatar = $avatar;
    }

    public function setGlobalName(string $globalName): void
    {
        $this->globalName = $globalName;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function setEmail(mixed $email): void
    {
        $this->email = $email;
    }

    public function hasActivePlatformSubscription(): bool
    {
        return $this->platformSubscription?->isActive() ?? false;
    }

    public function setPlatformSubscription(PlatformSubscription $platformSubscription): void
    {
        $this->platformSubscription = $platformSubscription;
    }

    public function getPlatformSubscription(): ?PlatformSubscription
    {
        return $this->platformSubscription;
    }
}
