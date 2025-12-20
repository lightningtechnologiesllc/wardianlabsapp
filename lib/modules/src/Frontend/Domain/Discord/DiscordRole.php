<?php
declare(strict_types=1);

namespace App\Frontend\Domain\Discord;

final readonly class DiscordRole
{
    public function __construct(
        private DiscordRoleId $id,
    ) {
    }

    public function getId(): DiscordRoleId
    {
        return $this->id;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->value(),
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            new DiscordRoleId($data['id']),
        );
    }
}
