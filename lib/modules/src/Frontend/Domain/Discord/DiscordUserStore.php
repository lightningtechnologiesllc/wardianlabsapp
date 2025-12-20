<?php
declare(strict_types=1);

namespace App\Frontend\Domain\Discord;

use App\Shared\Domain\Store;

final class DiscordUserStore
{
    const DISCORD_USER_KEY = 'discord_user';

    public function __construct(
        private readonly Store $store,
    ) {
    }

    public function save(DiscordUser $discordUser): void
    {
        $this->store->save(self::DISCORD_USER_KEY, $discordUser->toArray());
    }

    public function get(): ?DiscordUser
    {
        $userData = $this->store->get(self::DISCORD_USER_KEY);

        if ($userData === null) {
            return null;
        }

        return DiscordUser::fromArray($userData);
    }

    public function delete(): void
    {
        $this->store->delete(self::DISCORD_USER_KEY);
    }
}
