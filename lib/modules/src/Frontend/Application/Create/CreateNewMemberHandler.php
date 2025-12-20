<?php
declare(strict_types=1);

namespace App\Frontend\Application\Create;

use App\Frontend\Domain\Discord\DiscordId;
use App\Frontend\Domain\Discord\DiscordRole;
use App\Frontend\Domain\Discord\DiscordRoleId;
use App\Frontend\Domain\Discord\DiscordRoles;
use App\Frontend\Domain\Discord\GuildId;
use App\Frontend\Domain\Member\GuildMembership;
use App\Frontend\Domain\Member\GuildMemberships;
use App\Frontend\Domain\Member\Member;
use App\Frontend\Domain\Member\MemberRepository;
use App\Frontend\Domain\Stripe\StripeSubscriptions;
use App\Shared\Domain\EmailAddress;
use App\Shared\Domain\Tenant\TenantId;

final readonly class CreateNewMemberHandler
{
    public function __construct(
        private MemberRepository $memberRepository,
    ){}

    public function __invoke(
        TenantId $tenantId,
        string $guildId,
        string $stripeUserEmail,
        StripeSubscriptions $subscriptions,
        array $rolesToAssign
    ): void
    {
        $roles = [];

        foreach ($rolesToAssign as $roleId) {
            $roles[] = new DiscordRole(new DiscordRoleId($roleId));
        }

        $guildMembership = new GuildMembership(
            new GuildId($guildId),
            new DiscordRoles($roles)
        );

        $member = Member::createPending(
            $tenantId,
            new EmailAddress($stripeUserEmail),
            $subscriptions,
            new GuildMemberships([$guildMembership])
        );

        $this->memberRepository->save($member);
    }
}
