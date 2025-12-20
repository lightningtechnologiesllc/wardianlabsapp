<?php
declare(strict_types=1);

namespace App\Frontend\Domain\Member;

use App\Frontend\Domain\Discord\DiscordId;

interface MemberRepository
{
    public function findByMemberId(MemberId $id): ?Member;
    public function findByDiscordId(DiscordId $discordId): ?Member;
    public function findByLinkingToken(string $linkingToken): ?Member;
    public function findByCustomerEmail(string $email): ?Member;
    /**
     * @return Member[]
     */
    public function findAll(): array;
    public function save(Member $member): void;
}
