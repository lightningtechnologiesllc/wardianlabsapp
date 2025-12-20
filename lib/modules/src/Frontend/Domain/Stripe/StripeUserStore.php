<?php
declare(strict_types=1);

namespace App\Frontend\Domain\Stripe;

use App\Shared\Domain\Store;

final class StripeUserStore
{
    const STRIPE_USER_KEY = 'stripe_user';

    public function __construct(
        private readonly Store $store,
    ) {
    }

    public function save(StripeUser $user): void
    {
        $this->store->save(self::STRIPE_USER_KEY, $user->toArray());
    }

    public function get(): ?StripeUser
    {
        $userData = $this->store->get(self::STRIPE_USER_KEY);

        if ($userData === null) {
            return null;
        }

        return StripeUser::fromArray($userData);
    }

    public function delete(): void
    {
        $this->store->delete(self::STRIPE_USER_KEY);
    }
}
