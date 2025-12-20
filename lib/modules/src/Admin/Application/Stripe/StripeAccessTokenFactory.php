<?php
declare(strict_types=1);

namespace App\Admin\Application\Stripe;

use App\Shared\Domain\Stripe\StripeAccessToken;
use League\OAuth2\Client\Token\AccessToken;

final class StripeAccessTokenFactory
{
    public static function createFromLeague(AccessToken $accessToken): StripeAccessToken {
        return new StripeAccessToken(
            $accessToken->getToken(),
            $accessToken->getRefreshToken(),
            $accessToken->getValues()['stripe_user_id'],
            $accessToken->getValues()['stripe_publishable_key'],
            $accessToken->getValues()['scope'],
            $accessToken->getValues()['livemode'],
            $accessToken->getValues()['token_type'],
        );
    }
}
