<?php
declare(strict_types=1);

namespace App\Frontend\Domain\Member;

use App\Frontend\Domain\Discord\DiscordId;
use App\Frontend\Domain\Discord\DiscordRoles;
use App\Frontend\Domain\Discord\GuildId;

final readonly class GuildMembership
{
    public function __construct(
        private GuildId $guildId,
        private DiscordRoles $roles,
    ) {
    }

    public function getGuildId(): GuildId
    {
        return $this->guildId;
    }

    public function getRoles(): DiscordRoles
    {
        return $this->roles;
    }

    public function toArray(): array
    {
        return [
            'guild_id' => $this->guildId->value(),
            'roles' => $this->roles->toArray(),
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            new GuildId($data['guild_id']),
            DiscordRoles::fromArray($data['roles'])
        );
    }
}
