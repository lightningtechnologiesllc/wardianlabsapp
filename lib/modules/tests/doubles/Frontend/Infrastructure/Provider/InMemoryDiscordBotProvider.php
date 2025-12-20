<?php
declare(strict_types=1);

namespace Tests\Doubles\App\Frontend\Infrastructure\Provider;

use App\Frontend\Domain\Providers\DiscordUserManagerProvider;

final class InMemoryDiscordBotProvider implements DiscordUserManagerProvider
{
    private array $guildAdditions = [];
    private array $roleAdditions = [];
    private array $roleRemovals = [];
    private array $usersAlreadyInGuild = [];

    public function addUserToGuild(string $guildId, string $userId, string $userAccessToken): bool
    {
        $key = "$guildId:$userId";
        $wasAlreadyInGuild = in_array($key, $this->usersAlreadyInGuild, true);

        $this->guildAdditions[] = [
            'guildId' => $guildId,
            'userId' => $userId,
            'accessToken' => $userAccessToken,
        ];

        if (!$wasAlreadyInGuild) {
            $this->usersAlreadyInGuild[] = $key;
        }

        return !$wasAlreadyInGuild;
    }

    public function addRolesToUser(string $guildId, string $userId, array $roleIds): void
    {
        $this->roleAdditions[] = [
            'guildId' => $guildId,
            'userId' => $userId,
            'roleIds' => $roleIds,
        ];
    }

    public function removeRolesFromUser(string $guildId, string $userId, array $roleIds): void
    {
        $this->roleRemovals[] = [
            'guildId' => $guildId,
            'userId' => $userId,
            'roleIds' => $roleIds,
        ];
    }

    public function getRoleAdditions(): array
    {
        return $this->roleAdditions;
    }

    public function getRoleRemovals(): array
    {
        return $this->roleRemovals;
    }

    public function getGuildAdditions(): array
    {
        return $this->guildAdditions;
    }

    public function setUserAlreadyInGuild(string $guildId, string $userId): void
    {
        $this->usersAlreadyInGuild[] = "$guildId:$userId";
    }
}
