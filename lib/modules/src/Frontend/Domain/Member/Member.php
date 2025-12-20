<?php
declare(strict_types=1);

namespace App\Frontend\Domain\Member;

use App\Core\Types\Aggregate\AggregateRoot;
use App\Frontend\Domain\Discord\DiscordId;
use App\Frontend\Domain\Stripe\StripeSubscriptions;
use App\Shared\Domain\EmailAddress;
use App\Shared\Domain\Tenant\TenantId;

final class Member extends AggregateRoot
{
    public function __construct(
        private MemberId $id,
        private TenantId $tenantId,
        private EmailAddress $customerEmail,
        private StripeSubscriptions $subscriptions,
        private GuildMemberships $guildMemberships,
        private \DateTimeImmutable $createdAt,
        private ?DiscordId $discordUserId = null,
        private ?string $linkingToken = null,
        private ?\DateTimeImmutable $linkingTokenExpiresAt = null,
        private ?\DateTimeImmutable $linkedAt = null,
    ) {
    }

    /**
     * Create a pending member awaiting Discord linking
     */
    public static function createPending(
        TenantId $tenantId,
        EmailAddress $customerEmail,
        StripeSubscriptions $subscriptions,
        GuildMemberships $guildMemberships,
    ): self
    {
        return new self(
            id: MemberId::random(),
            tenantId: $tenantId,
            customerEmail: $customerEmail,
            subscriptions: $subscriptions,
            guildMemberships: $guildMemberships,
            createdAt: new \DateTimeImmutable(),
            discordUserId: null,
            linkingToken: self::generateLinkingToken(),
            linkingTokenExpiresAt: new \DateTimeImmutable('+7 days'),
            linkedAt: null,
        );
    }

    /**
     * Create a member already linked to Discord
     */
    public static function createLinked(
        TenantId $tenantId,
        EmailAddress $customerEmail,
        StripeSubscriptions $subscriptions,
        GuildMemberships $guildMemberships,
        DiscordId $discordUserId,
    ): self
    {
        return new self(
            id: MemberId::random(),
            tenantId: $tenantId,
            customerEmail: $customerEmail,
            subscriptions: $subscriptions,
            guildMemberships: $guildMemberships,
            createdAt: new \DateTimeImmutable(),
            discordUserId: $discordUserId,
            linkingToken: null,
            linkingTokenExpiresAt: null,
            linkedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Link this member to a Discord user
     */
    public function linkToDiscord(DiscordId $discordUserId): void
    {
        if ($this->isLinked()) {
            throw new \RuntimeException('Member is already linked to Discord');
        }

        if ($this->isLinkingTokenExpired()) {
            throw new \RuntimeException('Linking token has expired');
        }

        $this->discordUserId = $discordUserId;
        $this->linkedAt = new \DateTimeImmutable();
        $this->linkingToken = null;
        $this->linkingTokenExpiresAt = null;
    }

    /**
     * Update the member's subscriptions
     */
    public function updateSubscriptions(StripeSubscriptions $subscriptions): void
    {
        $this->subscriptions = $subscriptions;
    }

    /**
     * Update the member's guild memberships and roles
     */
    public function updateGuildMemberships(GuildMemberships $guildMemberships): void
    {
        $this->guildMemberships = $guildMemberships;
    }

    private static function generateLinkingToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    // State checks

    public function isPending(): bool
    {
        return $this->discordUserId === null;
    }

    public function isLinked(): bool
    {
        return $this->discordUserId !== null && $this->linkedAt !== null;
    }

    public function isLinkingTokenExpired(): bool
    {
        if ($this->linkingTokenExpiresAt === null) {
            return true;
        }

        return new \DateTimeImmutable() > $this->linkingTokenExpiresAt;
    }

    // Getters

    public function id(): MemberId
    {
        return $this->id;
    }

    public function getId(): MemberId
    {
        return $this->id;
    }

    public function getTenantId(): TenantId
    {
        return $this->tenantId;
    }

    public function getCustomerEmail(): EmailAddress
    {
        return $this->customerEmail;
    }

    public function getSubscriptions(): StripeSubscriptions
    {
        return $this->subscriptions;
    }

    public function getGuildMemberships(): GuildMemberships
    {
        return $this->guildMemberships;
    }

    public function getDiscordUserId(): ?DiscordId
    {
        return $this->discordUserId;
    }

    public function getLinkingToken(): ?string
    {
        return $this->linkingToken;
    }

    public function getLinkingTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->linkingTokenExpiresAt;
    }

    public function getLinkedAt(): ?\DateTimeImmutable
    {
        return $this->linkedAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    // Legacy compatibility methods (deprecated)

    /**
     * @deprecated Use getGuildMemberships() instead
     */
    public function getGuildId(): ?DiscordId
    {
        $firstMembership = $this->guildMemberships->first();
        return $firstMembership ? $firstMembership->getGuildId() : null;
    }

    /**
     * @deprecated Use getCustomerEmail() instead
     */
    public function getStripeUserEmail(): EmailAddress
    {
        return $this->customerEmail;
    }

    /**
     * @deprecated Use getGuildMemberships()[0]->getRoles() instead
     */
    public function getRolesToAssign()
    {
        $firstMembership = $this->guildMemberships->first();
        return $firstMembership ? $firstMembership->getRoles() : null;
    }
}
