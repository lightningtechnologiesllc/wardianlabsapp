<?php
declare(strict_types=1);

namespace App\Frontend\Infrastructure\Provider;

use App\Frontend\Domain\Providers\DiscordUserManagerProvider;
use App\Frontend\Domain\Providers\Exception\UnknownGuildIdException;
use App\Frontend\Domain\Providers\Exception\UnknownRoleIdException;
use App\Frontend\Domain\Providers\Exception\UnknownUserIdException;
use App\Shared\Domain\Discord\DiscordAccessToken;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ApiDiscordUserProvider
{
    private const API_ENDPOINT = 'https://discord.com/api/v10';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
    )
    {
    }

    public function getUserGuilds(DiscordAccessToken $userToken): array
    {
        try {
            $response = $this->httpClient->request(Request::METHOD_GET, self::API_ENDPOINT . '/users/@me/guilds', [
                'headers' => [
                    "Authorization" => "Bearer {$userToken->accessToken}",
                    'Content-Type' => 'application/json',
                ]
            ]);
        } catch (Exception $exception) {
            throw new Exception("Failed to fetch user guilds: " . $exception->getMessage());
        }

        if ($response->getStatusCode() !== 200) {
            throw new Exception("Failed to fetch user guilds: HTTP " . $response->getStatusCode());
        }

        return json_decode($response->getContent(), true);
    }
}
