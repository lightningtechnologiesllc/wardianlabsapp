<?php
declare(strict_types=1);

namespace App\Frontend\Infrastructure\Provider;

use App\Admin\Application\Stripe\StripeAccessTokenFactory;
use App\Frontend\Domain\Stripe\StripeProvider;
use App\Frontend\Domain\Stripe\StripeSubscription;
use App\Frontend\Domain\Stripe\StripeSubscriptions;
use App\Frontend\Domain\Tenant\TenantProvider;
use App\Shared\Domain\Stripe\StripeAccessToken;
use App\Shared\Domain\Stripe\StripeAccount;
use App\Shared\Domain\Stripe\StripeAccountDisconnectedException;
use App\Shared\Domain\Stripe\StripeAccountRepository;
use App\Shared\Domain\Tenant\TenantId;
use Closure;
use Exception;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Stripe\Collection;
use Stripe\Exception\ApiErrorException;
use Stripe\SearchResult;
use Stripe\StripeClient;
use Stripe\Subscription;

final class HttpStripeProvider implements StripeProvider
{
    const EXPIRED_API_KEY_ERROR_CODE = "platform_api_key_expired";

    private StripeAccount $stripeAccount;

    public function __construct(
        private readonly OAuth2ClientInterface   $OAuth2Client,
        private readonly TenantProvider          $tenantProvider,
        private readonly StripeAccountRepository $stripeAccountRepository,
        private readonly LoggerInterface         $logger,
    )
    {
    }

    public function hasValidSubscription(string $email): bool
    {
        $activeSubscriptions = $this->getValidSubscriptionsForUser($email);

        if ($activeSubscriptions->isEmpty()) {
            return false;
        }

        return true;
    }

    public function getValidSubscriptionsForUser(string $email, ?TenantId $tenantId = null): StripeSubscriptions
    {
        $stripeClient = $this->create($tenantId);

        $subscriptionsData = $this->fetchCollection($this->stripeAccount, fn(StripeClient $client) => $this->fetchCustomers($client, $email));

        $activeSubscriptions = [];
        foreach ($subscriptionsData->data as $customer) {
            foreach ($customer->subscriptions->data as $subscription) {
                $subscription = $this->buildSubscriptionFromStripeSubscription($subscription);
                if ($subscription->isActive()) {
                    $activeSubscriptions[] = $subscription;
                }
            }
        }

        return new StripeSubscriptions($activeSubscriptions);
    }

    public function create(?TenantId $tenantId = null): StripeClient
    {
        if ($tenantId === null) {
            $tenant = $this->tenantProvider->get();
            $tenantId = $tenant->getId();
            $tenantName = $tenant->getSubdomain();
        } else {
            $tenantName = $tenantId->value();
        }

        $stripeAccounts = $this->stripeAccountRepository->findByTenantId($tenantId);

        $this->stripeAccount = $stripeAccounts->first();

        $accessToken = $this->stripeAccount->getAccessToken();

        if (empty($accessToken)) {
            throw new Exception("Stripe Access Token is not configured for tenant: " . $tenantName);
        }

        return new StripeClient(['api_key' => $accessToken->accessToken]);
    }

    public function fetchCollection(
        StripeAccount $account,
        Closure      $fetchFunction
    ): Collection|SearchResult
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

    private function buildSubscriptionFromStripeSubscription(Subscription $data): StripeSubscription
    {
        return new StripeSubscription(
            $data->id,
            $data->plan->id,
            $data->status
        );
    }

    private function fetchCustomers(StripeClient $client, string $email): SearchResult
    {
        return $client->customers->search([
        'query' => "email:'" . addslashes($email) . "'",
        'expand' => ['data.subscriptions'],
    ]);

    }
}
