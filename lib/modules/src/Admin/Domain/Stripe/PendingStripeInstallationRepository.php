<?php
declare(strict_types=1);

namespace App\Admin\Domain\Stripe;

interface PendingStripeInstallationRepository
{
    public function save(PendingStripeInstallation $installation): void;

    public function findByLinkingToken(string $linkingToken): ?PendingStripeInstallation;

    public function findByStripeUserId(string $stripeUserId): ?PendingStripeInstallation;
}
