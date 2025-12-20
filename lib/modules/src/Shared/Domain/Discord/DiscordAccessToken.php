<?php
declare(strict_types=1);

namespace App\Shared\Domain\Discord;

final class DiscordAccessToken
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public int $expiresOn,
        public string $scope,
        public string $tokenType,
    )
    {}

    public function toArray(): array
    {
        return [
            'access_token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'expires_on' => $this->expiresOn,
            'scope' => $this->scope,
            'token_type' => $this->tokenType,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            accessToken: $data['access_token'],
            refreshToken: $data['refresh_token'],
            expiresOn: $data['expires_on'],
            scope: $data['scope'],
            tokenType: $data['token_type']
        );
    }
}
