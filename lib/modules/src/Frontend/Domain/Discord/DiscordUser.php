<?php
declare(strict_types=1);

namespace App\Frontend\Domain\Discord;

final class DiscordUser
{
    public function __construct(
        public string $id,
        public string $username,
        public ?string $avatar,
        public ?string $globalName
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'avatar' => $this->avatar,
            'global_name' => $this->globalName,
        ];
    }

    public static function fromArray(array $data): DiscordUser
    {
        if (empty($data)) {
            throw new \InvalidArgumentException('Data array cannot be empty.');
        }

        return new DiscordUser(
            id: $data['id'],
            username: $data['username'] ?? '',
            avatar: $data['avatar'] ?? null,
            globalName: $data['global_name'] ?? null
        );
    }
}
