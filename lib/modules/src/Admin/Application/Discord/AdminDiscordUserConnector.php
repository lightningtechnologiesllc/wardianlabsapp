<?php
declare(strict_types=1);

namespace App\Admin\Application\Discord;

use App\Admin\Domain\Tenant\Tenant;
use App\Shared\Domain\Tenant\TenantId;
use App\Admin\Domain\Tenant\TenantRepository;
use App\Admin\Domain\User\User;
use App\Admin\Domain\User\UserId;
use App\Admin\Domain\User\UserRepository;
use App\Frontend\Domain\Discord\DiscordId;
use League\OAuth2\Client\Token\AccessToken;

final readonly class AdminDiscordUserConnector
{
    public function __construct(
        private UserRepository $userRepository,
        private TenantRepository $tenantRepository,
    )
    {
    }

    public function __invoke(DiscordId $discordId, array $discordData, AccessToken $accessToken): User
    {
        $user = $this->userRepository->findOneByDiscordId($discordId);
        $discordAccessToken = DiscordAccessTokenFactory::createFromLeague($accessToken);

        if ($user) {
            $user->setAccessToken($discordAccessToken);
            $user->setUsername($discordData['username']);
            $user->setGlobalName($discordData['global_name'] ?? '');
            $user->setEmail($discordData['email'] ?? '');
            $user->setAvatar($discordData['avatar'] ?? '');
            $this->userRepository->save($user);
            return $user;
        }

        $user = new User(
            UserId::random(),
            new DiscordId($discordData['id']),
            $discordData['username'],
            $discordData['global_name'] ?? '',
            $discordData['email'] ?? '',
            $discordData['avatar'] ?? '',
            $discordAccessToken,
        );

        // Save user first so DoctrineUser exists in DB
        $this->userRepository->save($user);

        // Then create and save tenant
        $this->createTenantForUser($user);

        return $user;
    }

    private function createTenantForUser(User $user): void
    {
        $tenant = new Tenant(
            id: TenantId::random(),
            name: $user->getUsername() . "'s Tenant",
            subdomain: $this->sanitizeSubdomain($user->getUsername()),
            emailDSN: "",
            emailFromAddress: "",
        );
        $user->addTenant($tenant);

        $this->tenantRepository->save($tenant);
    }

    private function sanitizeSubdomain(string $username): string
    {
        // Convert to lowercase
        $subdomain = strtolower($username);

        // Replace invalid characters with hyphens
        $subdomain = preg_replace('/[^a-z0-9-]/', '-', $subdomain);

        // Remove consecutive hyphens
        $subdomain = preg_replace('/-+/', '-', $subdomain);

        // Remove leading and trailing hyphens
        $subdomain = trim($subdomain, '-');

        // Ensure minimum length of 3 characters
        if (strlen($subdomain) < 3) {
            $subdomain = $subdomain . '-tenant';
        }

        // Ensure maximum length of 63 characters (DNS label limit)
        if (strlen($subdomain) > 63) {
            $subdomain = substr($subdomain, 0, 63);
            // Re-trim trailing hyphens that might have been created by truncation
            $subdomain = rtrim($subdomain, '-');
        }

        return $subdomain;
    }
}
