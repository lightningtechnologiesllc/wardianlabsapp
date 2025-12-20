<?php
declare(strict_types=1);

namespace App\Shared\Infrastructure\Stripe;

use App\Admin\Application\Stripe\StripeAccessTokenFactory;
use App\Shared\Domain\Stripe\StripeAccessToken;
use App\Shared\Domain\Stripe\StripeClient;
use App\Shared\Domain\Stripe\StripeProviderAccount;
use App\Shared\Domain\Tenant\TenantId;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;

final readonly class ApiStripeClient implements StripeClient
{
    public function __construct(
        private ClientRegistry $clientRegistry,
    )
    {
    }

    public function refreshToken(StripeAccessToken $accessToken): StripeAccessToken
    {
        return StripeAccessTokenFactory::createFromLeague(
            $this->clientRegistry
                ->getClient('stripe_admin')
                ->refreshAccessToken($accessToken->refreshToken)
        );
    }

    public function retrieveAccount(StripeAccessToken $accessToken, TenantId $tenantId): StripeProviderAccount
    {
        $stripe = new \Stripe\StripeClient($accessToken->accessToken);
        $account = $stripe->accounts->retrieve(null, []);

        return new StripeProviderAccount(
            stripeProviderAccountId: $account->id,
            displayName: $account->settings->dashboard->display_name,
            accessToken: $accessToken
        );
    }
}
