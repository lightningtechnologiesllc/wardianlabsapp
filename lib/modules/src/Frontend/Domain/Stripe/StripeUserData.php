<?php
declare(strict_types=1);

namespace App\Frontend\Domain\Stripe;

final class StripeUserData
{
    public function __construct(
        public string $email = '',
        public string $otpCode = '',
    ) {
    }

    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'otp_code' => $this->otpCode,
        ];
    }

    public static function fromArray(array $data): self
    {
        if (empty($data)) {
            throw new \InvalidArgumentException('Data array cannot be empty.');
        }

        return new self(
            email: $data['email'] ?? '',
            otpCode: $data['otp_code'] ?? '',
        );
    }
}
