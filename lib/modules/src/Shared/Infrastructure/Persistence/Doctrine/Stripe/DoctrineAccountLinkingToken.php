<?php
declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine\Stripe;

use App\Shared\Domain\Stripe\AccountLinkingToken;
use App\Shared\Domain\Stripe\StripeCustomerSubscriptionId;
use App\Shared\Domain\Tenant\TenantId;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'account_linking_tokens')]
#[ORM\Index(name: 'IDX_STRIPE_SUBSCRIPTION', columns: ['stripe_subscription_id'])]
#[ORM\Index(name: 'IDX_CUSTOMER_EMAIL', columns: ['customer_email'])]
class DoctrineAccountLinkingToken
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column]
        private string $id,

        #[ORM\Column]
        private string $tenantId,

        #[ORM\Column]
        private string $stripeSubscriptionId,

        #[ORM\Column]
        private string $customerEmail,

        #[ORM\Column]
        private string $stripePriceId,

        #[ORM\Column(unique: true)]
        private string $linkingToken,

        #[ORM\Column]
        private \DateTimeImmutable $expiresAt,

        #[ORM\Column]
        private \DateTimeImmutable $createdAt,

        #[ORM\Column(nullable: true)]
        private ?string $discordUserId = null,

        #[ORM\Column(nullable: true)]
        private ?\DateTimeImmutable $linkedAt = null,
    )
    {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function getStripeSubscriptionId(): string
    {
        return $this->stripeSubscriptionId;
    }

    public function getCustomerEmail(): string
    {
        return $this->customerEmail;
    }

    public function getStripePriceId(): string
    {
        return $this->stripePriceId;
    }

    public function getLinkingToken(): string
    {
        return $this->linkingToken;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getDiscordUserId(): ?string
    {
        return $this->discordUserId;
    }

    public function setDiscordUserId(?string $discordUserId): void
    {
        $this->discordUserId = $discordUserId;
    }

    public function getLinkedAt(): ?\DateTimeImmutable
    {
        return $this->linkedAt;
    }

    public function setLinkedAt(?\DateTimeImmutable $linkedAt): void
    {
        $this->linkedAt = $linkedAt;
    }

    public function updateFromDomain(AccountLinkingToken $token): void
    {
        $this->discordUserId = $token->getDiscordUserId();
        $this->linkedAt = $token->getLinkedAt();
    }

    public function toDomain(): AccountLinkingToken
    {
        return new AccountLinkingToken(
            id: new StripeCustomerSubscriptionId($this->id),
            tenantId: new TenantId($this->tenantId),
            stripeSubscriptionId: $this->stripeSubscriptionId,
            customerEmail: $this->customerEmail,
            stripePriceId: $this->stripePriceId,
            linkingToken: $this->linkingToken,
            expiresAt: $this->expiresAt,
            createdAt: $this->createdAt,
            discordUserId: $this->discordUserId,
            linkedAt: $this->linkedAt,
        );
    }

    public static function fromDomain(AccountLinkingToken $token): self
    {
        return new self(
            id: $token->getId()->value(),
            tenantId: $token->getTenantId()->value(),
            stripeSubscriptionId: $token->getStripeSubscriptionId(),
            customerEmail: $token->getCustomerEmail(),
            stripePriceId: $token->getStripePriceId(),
            linkingToken: $token->getLinkingToken(),
            expiresAt: $token->getExpiresAt(),
            createdAt: $token->getCreatedAt(),
            discordUserId: $token->getDiscordUserId(),
            linkedAt: $token->getLinkedAt(),
        );
    }
}
