<?php
declare(strict_types=1);

namespace App\Frontend\Domain\Stripe;

final class StripeUser
{
    public function __construct(
        public string $email,
    ) {
    }

    public function toArray(): array
    {
        return [
            'email' => $this->email,
        ];
    }

    public static function fromArray(array $data): self
    {
        if (empty($data)) {
            throw new \InvalidArgumentException('Data array cannot be empty.');
        }

        return new self(
            email: $data['email'],
        );
    }
}
