<?php
declare(strict_types=1);

namespace Tests\Doubles\App\Frontend\Infrastructure\Persistence\Repository;

use App\Frontend\Domain\Discord\DiscordId;
use App\Frontend\Domain\Member\Member;
use App\Frontend\Domain\Member\MemberId;
use App\Frontend\Domain\Member\MemberRepository;

final class InMemoryMemberRepository implements MemberRepository
{
    public function __construct(
        private array $members = []
    ) {

    }

    public function findByMemberId(MemberId $id): ?Member
    {
        foreach ($this->members as $member) {
            if ($member->id()->equals($id)) {
                return $member;
            }
        }

        return null;
    }

    public function findByDiscordId(DiscordId $discordId): ?Member
    {
        foreach ($this->members as $member) {
            $memberDiscordId = $member->getDiscordUserId();
            if ($memberDiscordId && $memberDiscordId->value() === $discordId->value()) {
                return $member;
            }
        }

        return null;
    }

    public function findByLinkingToken(string $linkingToken): ?Member
    {
        foreach ($this->members as $member) {
            if ($member->getLinkingToken() === $linkingToken) {
                return $member;
            }
        }

        return null;
    }

    public function findByCustomerEmail(string $email): ?Member
    {
        foreach ($this->members as $member) {
            if ($member->getCustomerEmail()->value() === $email) {
                return $member;
            }
        }

        return null;
    }

    public function findAll(): array
    {
        return array_values($this->members);
    }

    public function save(Member $member): void
    {
        // Check if member already exists and update it
        foreach ($this->members as $key => $existingMember) {
            if ($existingMember->id()->equals($member->id())) {
                $this->members[$key] = $member;
                return;
            }
        }

        // If not found, add as new member
        $this->members[] = $member;
    }
}
