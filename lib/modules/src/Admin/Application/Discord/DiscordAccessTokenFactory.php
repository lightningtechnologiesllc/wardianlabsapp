<?php
declare(strict_types=1);

namespace App\Admin\Application\Discord;

use App\Shared\Domain\Discord\DiscordAccessToken;
use League\OAuth2\Client\Token\AccessToken;

final class DiscordAccessTokenFactory
{
    public static function createFromLeague(AccessToken $accessToken): DiscordAccessToken {
        return new DiscordAccessToken(
            $accessToken->getToken(),
            $accessToken->getRefreshToken(),
            $accessToken->getExpires(),
            $accessToken->getValues()['scope'],
            $accessToken->getValues()['token_type'],
        );
    }
}
