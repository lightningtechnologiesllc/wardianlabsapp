<?php
declare(strict_types=1);

namespace App\Admin\Infrastructure\Persistence\Doctrine\Stripe;

use App\Admin\Domain\Stripe\PendingStripeInstallation;
use App\Admin\Domain\Stripe\PendingStripeInstallationId;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'pending_stripe_installations')]
#[ORM\Index(name: 'IDX_STRIPE_USER_ID', columns: ['stripe_user_id'])]
class DoctrinePendingStripeInstallation
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column]
        private string $id,

        #[ORM\Column(unique: true)]
        private string $linkingToken,

        #[ORM\Column]
        private string $accessToken,

        #[ORM\Column]
        private string $refreshToken,

        #[ORM\Column]
        private string $stripeUserId,

        #[ORM\Column]
        private string $publishableKey,

        #[ORM\Column]
        private string $scope,

        #[ORM\Column]
        private bool $livemode,

        #[ORM\Column]
        private string $tokenType,

        #[ORM\Column]
        private string $email,

        #[ORM\Column]
        private \DateTimeImmutable $expiresAt,

        #[ORM\Column]
        private \DateTimeImmutable $createdAt,

        #[ORM\Column(nullable: true)]
        private ?\DateTimeImmutable $completedAt = null,
    ) {
    }

    public function updateFromDomain(PendingStripeInstallation $installation): void
    {
        $this->completedAt = $installation->getCompletedAt();
    }

    public function toDomain(): PendingStripeInstallation
    {
        return new PendingStripeInstallation(
            id: new PendingStripeInstallationId($this->id),
            linkingToken: $this->linkingToken,
            accessToken: $this->accessToken,
            refreshToken: $this->refreshToken,
            stripeUserId: $this->stripeUserId,
            publishableKey: $this->publishableKey,
            scope: $this->scope,
            livemode: $this->livemode,
            tokenType: $this->tokenType,
            email: $this->email,
            expiresAt: $this->expiresAt,
            createdAt: $this->createdAt,
            completedAt: $this->completedAt,
        );
    }

    public static function fromDomain(PendingStripeInstallation $installation): self
    {
        $stripeAccessToken = $installation->getStripeAccessToken();

        return new self(
            id: $installation->getId()->value(),
            linkingToken: $installation->getLinkingToken(),
            accessToken: $stripeAccessToken->accessToken,
            refreshToken: $stripeAccessToken->refreshToken,
            stripeUserId: $stripeAccessToken->stripeUserId,
            publishableKey: $stripeAccessToken->publishableKey,
            scope: $stripeAccessToken->scope,
            livemode: $stripeAccessToken->livemode,
            tokenType: $stripeAccessToken->tokenType,
            email: $installation->getEmail(),
            expiresAt: $installation->getExpiresAt(),
            createdAt: $installation->getCreatedAt(),
            completedAt: $installation->getCompletedAt(),
        );
    }
}
