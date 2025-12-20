<?php
declare(strict_types=1);

namespace App\Shared\Domain\Stripe;


use App\Shared\Domain\Tenant\TenantId;

interface StripeAccountRepository
{
    public function find(StripeAccountId $id): ?StripeAccount;
    public function save(StripeAccount $account): void;
    public function findByTenantId(TenantId $tenantId): StripeAccounts;
    public function findByStripeProviderAccountId(string $stripeProviderAccountId): ?StripeAccount;
    public function delete(StripeAccount $account): void;
    public function saveAccessToken(StripeAccountId $accountId, StripeAccessToken $refreshedToken);
    public function updateStripeProviderAccount(StripeAccountId $accountId, StripeProviderAccount $stripeProviderAccount);
}
