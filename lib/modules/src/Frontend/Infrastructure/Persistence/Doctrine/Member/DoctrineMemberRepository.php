<?php
declare(strict_types=1);

namespace App\Frontend\Infrastructure\Persistence\Doctrine\Member;

use App\Frontend\Domain\Discord\DiscordId;
use App\Frontend\Domain\Discord\DiscordRoles;
use App\Frontend\Domain\Member\GuildMemberships;
use App\Frontend\Domain\Member\Member;
use App\Frontend\Domain\Member\MemberId;
use App\Frontend\Domain\Member\MemberRepository;
use App\Frontend\Domain\Stripe\StripeSubscriptions;
use App\Shared\Domain\EmailAddress;
use App\Shared\Domain\Tenant\TenantId;
use App\Shared\Infrastructure\Persistence\Doctrine\DoctrineRepository;

class DoctrineMemberRepository extends DoctrineRepository implements MemberRepository
{
    public function save(Member $member): void
    {
        // Check if this member already exists in the database
        $existingDoctrineMember = $this->repository(DoctrineMember::class)->findOneBy([
            'memberId' => $member->getId()->value(),
        ]);

        if ($existingDoctrineMember) {
            // Update existing entity
            $existingDoctrineMember->tenantId = $member->getTenantId()->value();
            $existingDoctrineMember->customerEmail = $member->getCustomerEmail()->value();
            $existingDoctrineMember->subscriptions = $member->getSubscriptions()->toArray();
            $existingDoctrineMember->guildMemberships = $member->getGuildMemberships()->toArray();
            $existingDoctrineMember->discordUserId = $member->getDiscordUserId()?->value();
            $existingDoctrineMember->linkingToken = $member->getLinkingToken();
            $existingDoctrineMember->linkingTokenExpiresAt = $member->getLinkingTokenExpiresAt();
            $existingDoctrineMember->linkedAt = $member->getLinkedAt();

            $this->entityManager()->flush();
        } else {
            // Create new entity
            $this->persist($this->toDoctrine($member));
        }
    }

    public function findByMemberId(MemberId $id): ?Member
    {
        $this->entityManager()->clear();

        $doctrineMember = $this->repository(DoctrineMember::class)->findOneBy([
            'memberId' => $id,
        ]);

        if (null === $doctrineMember) {
            return null;
        }

        return self::fromDoctrine($doctrineMember);
    }

    public function findByDiscordId(DiscordId $discordId): ?Member
    {
        $this->entityManager()->clear();

        $doctrineMember = $this->repository(DoctrineMember::class)->findOneBy([
            'discordUserId' => $discordId->value(),
        ]);

        if (null === $doctrineMember) {
            return null;
        }

        return self::fromDoctrine($doctrineMember);
    }


    public function findByLinkingToken(string $linkingToken): ?Member
    {
        $this->entityManager()->clear();

        $doctrineMember = $this->repository(DoctrineMember::class)->findOneBy([
            'linkingToken' => $linkingToken,
        ]);

        if (null === $doctrineMember) {
            return null;
        }

        return self::fromDoctrine($doctrineMember);
    }

    public function findByCustomerEmail(string $email): ?Member
    {
        $this->entityManager()->clear();

        $doctrineMember = $this->repository(DoctrineMember::class)->findOneBy([
            'customerEmail' => $email,
        ]);

        if (null === $doctrineMember) {
            return null;
        }

        return self::fromDoctrine($doctrineMember);
    }

    public function findAll(): array
    {
        $this->entityManager()->clear();

        $doctrineMembers = $this->repository(DoctrineMember::class)->findAll();

        return array_map(
            fn(DoctrineMember $doctrineMember) => self::fromDoctrine($doctrineMember),
            $doctrineMembers
        );
    }

    public static function fromDoctrine(DoctrineMember $doctrineMember): Member
    {
        return new Member(
            id: new MemberId($doctrineMember->memberId),
            tenantId: new TenantId($doctrineMember->tenantId),
            customerEmail: new EmailAddress($doctrineMember->customerEmail),
            subscriptions: StripeSubscriptions::fromArray($doctrineMember->subscriptions),
            guildMemberships: GuildMemberships::fromArray($doctrineMember->guildMemberships),
            createdAt: $doctrineMember->createdAt,
            discordUserId: $doctrineMember->discordUserId ? new DiscordId($doctrineMember->discordUserId) : null,
            linkingToken: $doctrineMember->linkingToken,
            linkingTokenExpiresAt: $doctrineMember->linkingTokenExpiresAt,
            linkedAt: $doctrineMember->linkedAt,
        );
    }

    public static function toDoctrine(Member $member): DoctrineMember
    {
        return new DoctrineMember(
            memberId: $member->getId()->value(),
            tenantId: $member->getTenantId()->value(),
            customerEmail: $member->getCustomerEmail()->value(),
            subscriptions: $member->getSubscriptions()->toArray(),
            guildMemberships: $member->getGuildMemberships()->toArray(),
            createdAt: $member->getCreatedAt(),
            discordUserId: $member->getDiscordUserId()?->value(),
            linkingToken: $member->getLinkingToken(),
            linkingTokenExpiresAt: $member->getLinkingTokenExpiresAt(),
            linkedAt: $member->getLinkedAt(),
        );
    }
}
