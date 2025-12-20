<?php
declare(strict_types=1);

namespace Tests\Doubles\App\Frontend\Infrastructure\Provider;

use App\Frontend\Domain\Providers\DiscordUserManagerProvider;

final class InMemoryDiscordBotProvider implements DiscordUserManagerProvider
{
    private array $roleAdditions = [];
    private array $roleRemovals = [];

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
}
