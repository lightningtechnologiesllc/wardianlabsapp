<?php
declare(strict_types=1);

namespace App\Frontend\Domain\Discord;

use App\Core\Types\Collection\Collection;

final class DiscordRoles extends Collection
{
    protected function type(): string
    {
        return DiscordRole::class;
    }

    public static function fromArray(array $items): DiscordRoles
    {
        $subscriptions = [];
        foreach ($items as $indicatorData) {
            $subscriptions[] = DiscordRole::fromArray($indicatorData);
        }

        return new DiscordRoles($subscriptions);
    }

    public function toArray(): array
    {
        return $this->map(function (DiscordRole $discordRole) {
            return $discordRole->toArray();
        });
    }
}
