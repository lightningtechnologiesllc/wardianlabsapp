<?php
declare(strict_types=1);

namespace App\Frontend\Domain\Member;

use App\Core\Types\Collection\Collection;

final class GuildMemberships extends Collection
{
    protected function type(): string
    {
        return GuildMembership::class;
    }

    public static function fromArray(array $items): self
    {
        $memberships = [];
        foreach ($items as $item) {
            $memberships[] = GuildMembership::fromArray($item);
        }

        return new self($memberships);
    }

    public function toArray(): array
    {
        return $this->map(function (GuildMembership $membership) {
            return $membership->toArray();
        });
    }
}
