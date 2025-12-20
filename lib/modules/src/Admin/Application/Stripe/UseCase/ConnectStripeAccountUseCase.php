<?php
declare(strict_types=1);

namespace App\Admin\Application\Stripe\UseCase;

use App\Shared\Domain\Stripe\StripeAccessToken;
use App\Shared\Domain\Stripe\StripeAccount;
use App\Shared\Domain\Stripe\StripeAccountId;
use App\Shared\Domain\Stripe\StripeAccountRepository;
use App\Shared\Domain\Stripe\StripeClient;
use App\Shared\Domain\Tenant\TenantId;

final readonly class ConnectStripeAccountUseCase
{
    public function __construct(
        private StripeClient $stripeClient,
        private StripeAccountRepository $accountRepository
    )
    {
    }

    public function __invoke(StripeAccessToken $accessToken, TenantId $tenantId): void
    {
        $stripeProviderAccount = $this->stripeClient->retrieveAccount($accessToken, $tenantId);

        $foundAccounts = $this->accountRepository->findByTenantId($tenantId);

        $accountsWithStripeProviderAccountId = $foundAccounts->filter(function (StripeAccount $foundAccount) use ($stripeProviderAccount) {
            return $foundAccount->getStripeProviderAccountId() === $stripeProviderAccount->stripeProviderAccountId;
        });

        if ($accountsWithStripeProviderAccountId) {
            $this->accountRepository->updateStripeProviderAccount($accountsWithStripeProviderAccountId[0]->getAccountId(), $stripeProviderAccount);
            return;
        }

        $account = new StripeAccount(
            accountId: StripeAccountId::random(),
            tenantId: $tenantId,
            stripeProviderAccountId: $stripeProviderAccount->stripeProviderAccountId,
            displayName: $stripeProviderAccount->displayName,
            accessToken: $stripeProviderAccount->accessToken,
        );

        $this->accountRepository->save($account);
    }
}
