<?php
declare(strict_types=1);

namespace App\Frontend\Domain\Providers;

interface DiscordUserManagerProvider
{
    public function addRolesToUser(string $guildId, string $userId, array $roleIds): void;
    public function removeRolesFromUser(string $guildId, string $userId, array $roleIds): void;
}
