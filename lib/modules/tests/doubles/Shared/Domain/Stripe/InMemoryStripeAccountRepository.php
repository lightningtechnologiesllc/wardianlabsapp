<?php
declare(strict_types=1);

namespace Tests\Doubles\App\Shared\Domain\Stripe;

use App\Shared\Domain\Stripe\StripeAccessToken;
use App\Shared\Domain\Stripe\StripeAccount;
use App\Shared\Domain\Stripe\StripeAccountId;
use App\Shared\Domain\Stripe\StripeAccountRepository;
use App\Shared\Domain\Stripe\StripeAccounts;
use App\Shared\Domain\Stripe\StripeProviderAccount;
use App\Shared\Domain\Tenant\TenantId;
use Exception;

final class InMemoryStripeAccountRepository implements StripeAccountRepository
{
    private array $stripeAccounts = [];


    public function find(StripeAccountId $id): ?StripeAccount
    {
        /** @var StripeAccount $existingTenant */
        foreach ($this->stripeAccounts as $existingTenant) {
            if ($existingTenant->getAccountId()->equals($id)) {
                return $existingTenant;
            }
        }

        return null;
    }

    public function save(StripeAccount $account): void
    {
        /** @var StripeAccount $existingTenant */
        foreach ($this->stripeAccounts as $key => $existingTenant) {
            if ($existingTenant->getAccountId()->equals($account->getAccountId())) {
                $this->stripeAccounts[$key] = $account;
                return;
            }
        }

        $this->stripeAccounts[] = $account;
    }

    public function findByTenantId(TenantId $tenantId): StripeAccounts
    {
        return new StripeAccounts(
            array_filter($this->stripeAccounts, function (StripeAccount $stripeAccount) use ($tenantId) {
                return $stripeAccount->getTenantId()->equals($tenantId);
            })
        );
    }

    public function findByStripeProviderAccountId(string $stripeProviderAccountId): ?StripeAccount
    {
        /** @var StripeAccount $stripeAccount */
        foreach ($this->stripeAccounts as $stripeAccount) {
            if ($stripeAccount->getStripeProviderAccountId() === $stripeProviderAccountId) {
                return $stripeAccount;
            }
        }

        return null;
    }

    public function delete(StripeAccount $account): void
    {
        throw new Exception("Not implemented");
    }

    public function saveAccessToken(StripeAccountId $accountId, StripeAccessToken $refreshedToken)
    {
        throw new Exception("Not implemented");
    }

    public function updateStripeProviderAccount(StripeAccountId $accountId, StripeProviderAccount $stripeProviderAccount)
    {
        $account = $this->find($accountId);

        $account->setStripeProviderAccountId($stripeProviderAccount->stripeProviderAccountId);
        $account->setDisplayName($stripeProviderAccount->displayName);
        $account->setAccessToken($stripeProviderAccount->accessToken);
    }
}
