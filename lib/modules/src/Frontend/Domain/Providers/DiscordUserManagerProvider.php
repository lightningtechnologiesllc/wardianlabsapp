<?php
declare(strict_types=1);

namespace App\Frontend\Domain\Providers;

interface DiscordUserManagerProvider
{
    /**
     * Add a user to a guild using their OAuth2 access token.
     * Returns true if user was added, false if user was already in the guild.
     */
    public function addUserToGuild(string $guildId, string $userId, string $userAccessToken): bool;
    public function addRolesToUser(string $guildId, string $userId, array $roleIds): void;
    public function removeRolesFromUser(string $guildId, string $userId, array $roleIds): void;
}
