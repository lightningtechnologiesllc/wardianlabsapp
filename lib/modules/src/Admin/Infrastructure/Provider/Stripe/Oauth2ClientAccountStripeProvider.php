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

    public function getAccountEmail(StripeAccessToken $accessToken): ?string
    {
        try {
            $stripeClient = new StripeClient(['api_key' => $accessToken->accessToken]);
            $account = $stripeClient->accounts->retrieve($accessToken->stripeUserId);

            // Try multiple sources for email
            $email = $account->email;

            if (empty($email) && !empty($account->business_profile->support_email)) {
                $email = $account->business_profile->support_email;
            }

            if (empty($email) && !empty($account->individual->email)) {
                $email = $account->individual->email;
            }

            // Try to get email from account representative
            if (empty($email)) {
                $email = $this->getRepresentativeEmail($stripeClient, $accessToken->stripeUserId);
            }

            $this->logger->info('Fetched Stripe account details', [
                'stripe_user_id' => $accessToken->stripeUserId,
                'email' => $email,
                'account_email' => $account->email,
                'support_email' => $account->business_profile->support_email ?? null,
            ]);

            return $email;
        } catch (ApiErrorException $e) {
            $this->logger->error('Failed to fetch Stripe account details', [
                'stripe_user_id' => $accessToken->stripeUserId,
                'error' => $e->getMessage(),
            ]);
            return null;
        } catch (Exception $e) {
            $this->logger->error('Unexpected error fetching Stripe account', [
                'stripe_user_id' => $accessToken->stripeUserId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function getRepresentativeEmail(StripeClient $stripeClient, string $accountId): ?string
    {
        try {
            $personsList = $stripeClient->accounts->allPersons($accountId, ['limit' => 10]);

            $this->logger->debug("persons found", ['persons' => $personsList->toArray()]);

            // Retrieve full person data for each person
            $representativeEmail = null;
            $anyEmail = null;

            foreach ($personsList->data as $personSummary) {
                $person = $stripeClient->accounts->retrievePerson($accountId, $personSummary->id);

                $this->logger->debug('Full person data', [
                    'person_id' => $person->id,
                    'email' => $person->email ?? null,
                    'relationship' => $person->relationship?->toArray() ?? null,
                ]);

                // Check if this person is a representative
                if (isset($person->relationship->representative) && $person->relationship->representative) {
                    if (!empty($person->email)) {
                        $this->logger->info('Found representative email', [
                            'account_id' => $accountId,
                            'person_id' => $person->id,
                            'email' => $person->email,
                        ]);
                        return $person->email;
                    }
                }

                // Keep track of any email we find
                if ($anyEmail === null && !empty($person->email)) {
                    $anyEmail = $person->email;
                }
            }

            // If no representative found, return any email we found
            if ($anyEmail !== null) {
                $this->logger->info('Found person email (non-representative)', [
                    'account_id' => $accountId,
                    'email' => $anyEmail,
                ]);
                return $anyEmail;
            }
        } catch (Exception $e) {
            $this->logger->warning('Could not fetch account persons', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
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
