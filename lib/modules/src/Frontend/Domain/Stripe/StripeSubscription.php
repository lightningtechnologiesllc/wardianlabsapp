<?php
declare(strict_types=1);

namespace App\Frontend\Domain\Stripe;

final class StripeSubscription
{
    public function __construct(
        private string $id,
        private string $planId,
        private string $status,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getPlanId(): string
    {
        return $this->planId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'planId' => $this->planId,
            'status' => $this->status,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            $data['planId'],
            $data['status'],
        );
    }
}
