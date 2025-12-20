<?php
declare(strict_types=1);

namespace App\Shared\Domain\Stripe;

interface AccountLinkingTokenRepository
{
    public function save(AccountLinkingToken $token): void;

    public function findByLinkingToken(string $linkingToken): ?AccountLinkingToken;

    public function findByStripeSubscriptionId(string $stripeSubscriptionId): ?AccountLinkingToken;

    public function findActiveByCustomerEmail(string $email): array;
}
