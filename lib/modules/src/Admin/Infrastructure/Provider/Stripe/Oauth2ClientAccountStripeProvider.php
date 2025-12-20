<?php
declare(strict_types=1);

namespace App\Admin\Infrastructure\Provider\Stripe;

use App\Admin\Application\Stripe\StripeAccessTokenFactory;
use App\Shared\Domain\Stripe\StripeAccessToken;
use App\Shared\Domain\Stripe\StripeAccount;
use App\Shared\Domain\Stripe\StripeAccountDisconnectedException;
use App\Shared\Domain\Stripe\StripeAccountRepository;
use Closure;
use Exception;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Stripe\Collection;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

final class Oauth2ClientAccountStripeProvider implements AccountStripeProvider
{
    const EXPIRED_API_KEY_ERROR_CODE = "platform_api_key_expired";

    public function __construct(
        private OAuth2ClientInterface   $OAuth2Client,
        private StripeAccountRepository $stripeAccountRepository,
        private LoggerInterface         $logger,
    )
    {
    }

    public function getPricesForAccount(StripeAccount $account): StripePrices
    {
        $prices = $this->fetchCollection($account, fn(StripeClient $client) => $this->fetchPrices($client));
        $products = $this->fetchCollection($account, fn(StripeClient $client) => $this->fetchProducts($client));

        return StripePrices::fromStripeCollections($prices, $products);
    }

    public function getCustomerEmail(StripeAccount $account, string $customerId): ?string
    {
        try {
            $accessToken = $account->getAccessToken();
            $stripeClient = new StripeClient(['api_key' => $accessToken->accessToken]);

            $customer = $stripeClient->customers->retrieve($customerId);

            $this->logger->info('Fetched customer from Stripe', [
                'customer_id' => $customerId,
                'email' => $customer->email,
            ]);

            return $customer->email;
        } catch (ApiErrorException $e) {
            if ($e->getError()->code === self::EXPIRED_API_KEY_ERROR_CODE) {
                $accessToken = $this->refreshToken($account, $accessToken);
                $stripeClient = new StripeClient(['api_key' => $accessToken->accessToken]);
                try {
                    $customer = $stripeClient->customers->retrieve($customerId);
                    return $customer->email;
                } catch (Exception $e) {
                    $this->logger->error('Failed to fetch customer after token refresh', [
                        'customer_id' => $customerId,
                        'error' => $e->getMessage(),
                    ]);
                    return null;
                }
            } else {
                $this->logger->error('Failed to fetch customer from Stripe', [
                    'customer_id' => $customerId,
                    'error' => $e->getMessage(),
                ]);
                return null;
            }
        } catch (Exception $e) {
            $this->logger->error('Unexpected error fetching customer', [
                'customer_id' => $customerId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function fetchPrices(StripeClient $stripeClient): Collection
    {
        return $stripeClient->prices->all(['limit' => 100, 'active' => true]);
    }

    private function fetchProducts(StripeClient $stripeClient): Collection
    {
        return $stripeClient->products->all(['limit' => 100]);
    }

    public function fetchCollection(
        StripeAccount $account,
        Closure      $fetchFunction
    ): Collection
    {
        $accessToken = $account->getAccessToken();
        $stripeClient = new StripeClient(['api_key' => $accessToken->accessToken]);

        try {
            $collection = $fetchFunction($stripeClient);
        } catch (ApiErrorException $e) {
            if ($e->getError()->code === self::EXPIRED_API_KEY_ERROR_CODE) {
                $accessToken = $this->refreshToken($account, $accessToken);
                $stripeClient = new StripeClient(['api_key' => $accessToken->accessToken]);
                try {
                    $collection = $fetchFunction($stripeClient);
                } catch (Exception $e) {
                    throw new RuntimeException('Failed to fetch collection after refreshing token: ' . $e->getMessage());
                }
            } else {
                throw new RuntimeException('Failed to fetch collection: ' . $e->getMessage());
            }
        }

        return $collection;
    }

    private function refreshToken(StripeAccount $account, StripeAccessToken $accessToken): StripeAccessToken
    {
        try {
            $refreshedToken = StripeAccessTokenFactory::createFromLeague(
                $this->OAuth2Client->refreshAccessToken($accessToken->refreshToken)
            );
        } catch (IdentityProviderException $e) {
            $this->logger->error('Failed to refresh Stripe OAuth token - account may be disconnected', [
                'account_id' => $account->getAccountId()->value(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new StripeAccountDisconnectedException(
                $account->getAccountId(),
                $e->getMessage(),
                $e
            );
        }

        $account->setAccessToken($refreshedToken);

        $this->logger->info("Refreshed token", ["token" => $refreshedToken->toArray()]);
        $this->stripeAccountRepository->saveAccessToken($account->getAccountId(), $refreshedToken);
        return $refreshedToken;
    }
}
