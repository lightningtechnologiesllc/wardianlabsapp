<?php
declare(strict_types=1);

namespace App\Frontend\Infrastructure\Provider;

use App\Frontend\Domain\Discord\GuildId;
use App\Frontend\Domain\Providers\DiscordUserManagerProvider;
use App\Frontend\Domain\Providers\Exception\MissingPermissionsException;
use App\Frontend\Domain\Providers\Exception\UnknownGuildIdException;
use App\Frontend\Domain\Providers\Exception\UnknownRoleIdException;
use App\Frontend\Domain\Providers\Exception\UnknownUserIdException;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ApiDiscordBotManagerProvider implements DiscordUserManagerProvider
{
    private const API_ENDPOINT = 'https://discord.com/api/v10';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string              $botToken
    )
    {
    }

    public function getGuildData(GuildId $guildId): array
    {
        try {
            $response = $this->httpClient->request(Request::METHOD_GET, self::API_ENDPOINT . "/guilds/{$guildId->value()}", [
                'headers' => [
                    "Authorization" => "Bot {$this->botToken}",
                    'Content-Type' => 'application/json',
                ]
            ]);
        } catch (Exception $exception) {
            throw new Exception("Failed to fetch guild: " . $exception->getMessage());
        }

        if ($response->getStatusCode() !== 200) {
            if ($response->getStatusCode() === 404) {
                throw new UnknownGuildIdException($guildId->value());
            }
            throw new Exception("Failed to fetch guild: HTTP " . $response->getStatusCode());
        }

        return json_decode($response->getContent(), true);
    }

    public function addUserToGuild(string $guildId, string $userId, string $userAccessToken): bool
    {
        if (empty($guildId) || empty($userId) || empty($userAccessToken)) {
            throw new Exception('Guild ID, User ID, and User Access Token must be provided.');
        }

        try {
            $response = $this->httpClient->request(
                Request::METHOD_PUT,
                self::API_ENDPOINT . "/guilds/$guildId/members/$userId",
                [
                    'headers' => [
                        'Authorization' => "Bot {$this->botToken}",
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'access_token' => $userAccessToken,
                    ],
                ]
            );
        } catch (Exception $exception) {
            throw new Exception("Failed to add user $userId to guild $guildId: " . $exception->getMessage());
        }

        $statusCode = $response->getStatusCode();

        // 201 = user was added to guild
        // 204 = user was already in guild
        if ($statusCode === 201) {
            return true;
        }

        if ($statusCode === 204) {
            return false;
        }

        $responseContent = json_decode($response->getContent(false), true);

        if (isset($responseContent['code'])) {
            $this->throwException($responseContent['code'], $guildId, null, $userId);
        }

        throw new Exception("Failed to add user $userId to guild $guildId: HTTP $statusCode");
    }

    public function addRolesToUser(string $guildId, string $userId, array $roleIds): void
    {
        if (empty($guildId) || empty($userId) || empty($roleIds)) {
            throw new Exception('Tenant ID, User ID, and Role IDs must be provided.');
        }

        foreach ($roleIds as $roleId) {
            try {
                $response = $this->httpClient->request(Request::METHOD_PUT, self::API_ENDPOINT . "/guilds/$guildId/members/$userId/roles/$roleId", [
                    'headers' => [
                        "Authorization" => "Bot {$this->botToken}",
                        'Content-Type' => 'application/json',
                    ]
                ]);
            } catch (Exception $exception) {
                throw new Exception("Failed to add role $roleId to user $userId in server $guildId: " . $exception->getMessage());
            }

            if ($response->getStatusCode() !== 200) {
                $responseContent = json_decode($response->getContent(false), true);

                if (isset($responseContent['code'])) {
                    $this->throwException($responseContent['code'], $guildId, $roleId, $userId);
                }
            }
        }
    }

    public function removeRolesFromUser(string $guildId, string $userId, array $roleIds): void
    {
        if (empty($guildId) || empty($userId) || empty($roleIds)) {
            throw new Exception('Tenant ID, User ID, and Role IDs must be provided.');
        }

        foreach ($roleIds as $roleId) {
            try {
                $response = $this->httpClient->request(Request::METHOD_DELETE, self::API_ENDPOINT . "/guilds/$guildId/members/$userId/roles/$roleId", [
                    'headers' => [
                        "Authorization" => "Bot {$this->botToken}",
                        'Content-Type' => 'application/json',
                    ]
                ]);
            } catch (Exception $exception) {
                throw new Exception("Failed to remove role $roleId from user $userId in server $guildId: " . $exception->getMessage());
            }

            if ($response->getStatusCode() !== 204) {
                $responseContent = json_decode($response->getContent(false), true);

                if (isset($responseContent['code'])) {
                    $this->throwException($responseContent['code'], $guildId, $roleId, $userId);
                }
            }
        }
    }

    public function throwException($code, string $guildId, mixed $roleId, string $userId): void
    {
        switch ($code) {
            case 10004:
                throw new UnknownGuildIdException($guildId);
            case 10011:
                if ($roleId !== null) {
                    throw new UnknownRoleIdException($roleId);
                }
                throw new Exception("Unknown role error for user $userId in guild $guildId.");
            case 10013:
                throw new UnknownUserIdException($userId);
            case 50013:
                $context = $roleId !== null
                    ? "add role $roleId to user $userId in guild $guildId"
                    : "add user $userId to guild $guildId";
                throw new MissingPermissionsException("Missing permissions to $context.");
            default:
                $context = $roleId !== null
                    ? "guild $guildId, role $roleId, and user $userId"
                    : "guild $guildId and user $userId";
                throw new Exception("Unknown error occurred with code $code for $context.");
        }
    }
}
