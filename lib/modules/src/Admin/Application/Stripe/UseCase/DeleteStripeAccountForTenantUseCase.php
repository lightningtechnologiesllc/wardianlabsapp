<?php
declare(strict_types=1);

namespace App\Admin\Application\Stripe\UseCase;

use App\Admin\Domain\User\User;
use App\Shared\Domain\Stripe\StripeAccountId;
use App\Shared\Domain\Stripe\StripeAccountRepository;

final readonly class DeleteStripeAccountForTenantUseCase
{
    public function __construct(
        private readonly StripeAccountRepository $stripeAccountRepository,
    )
    {
    }

    public function __invoke(User $user, StripeAccountId $accountId): void
    {
        $account = $this->stripeAccountRepository->find($accountId);

        if (null === $account) {
            throw new \RuntimeException('Stripe account not found: ' . $accountId->value());
        }

        // Check if the user owns a tenant that owns this Stripe account
        $userOwnsTenant = false;
        foreach ($user->getTenants() as $tenant) {
            if ($account->getTenantId()->equals($tenant->getId())) {
                $userOwnsTenant = true;
                break;
            }
        }

        if (!$userOwnsTenant) {
            throw new \RuntimeException('User does not have permission to delete this Stripe account');
        }

        $this->stripeAccountRepository->delete($account);
    }
}
