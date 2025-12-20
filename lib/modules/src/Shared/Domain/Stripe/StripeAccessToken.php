<?php
declare(strict_types=1);

namespace App\Shared\Domain\Stripe;

final readonly class StripeAccessToken
{
    public function __construct(
        #[SensitiveParameter] public string $accessToken,
        #[SensitiveParameter] public string $refreshToken,
        public string $stripeUserId,
        public string $publishableKey,
        public string $scope,
        public bool $livemode,
        public string $tokenType,
    )
    {
    }

    public function toArray(): array
    {
        return [
            'access_token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'stripe_user_id' => $this->stripeUserId,
            'publishable_key' => $this->publishableKey,
            'scope' => $this->scope,
            'livemode' => $this->livemode,
            'token_type' => $this->tokenType,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['access_token'],
            $data['refresh_token'],
            $data['stripe_user_id'],
            $data['publishable_key'],
            $data['scope'],
            $data['livemode'],
            $data['token_type'],
        );
    }
}
