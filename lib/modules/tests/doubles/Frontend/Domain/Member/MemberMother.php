<?php
declare(strict_types=1);

namespace Tests\Doubles\App\Frontend\Domain\Member;

use App\Frontend\Domain\Discord\DiscordId;
use App\Frontend\Domain\Discord\DiscordRole;
use App\Frontend\Domain\Discord\DiscordRoleId;
use App\Frontend\Domain\Discord\DiscordRoles;
use App\Frontend\Domain\Discord\GuildId;
use App\Frontend\Domain\Member\GuildMembership;
use App\Frontend\Domain\Member\GuildMemberships;
use App\Frontend\Domain\Member\Member;
use App\Frontend\Domain\Stripe\StripeSubscription;
use App\Frontend\Domain\Stripe\StripeSubscriptions;
use App\Shared\Domain\EmailAddress;
use Tests\Doubles\App\Frontend\Domain\DiscordId\DiscordIdMother;
use Tests\Doubles\App\Shared\Domain\Tenant\TenantIdMother;

final class MemberMother
{
    public static function random(): Member
    {
        $guildId = GuildId::random();
        $roles = new DiscordRoles([new DiscordRole(new DiscordRoleId("1398028474089738423"))]);
        $guildMembership = new GuildMembership($guildId, $roles);

        return new Member(
            id: MemberIdMother::create(),
            tenantId: TenantIdMother::create(),
            customerEmail: new EmailAddress('test@techabreath.com'),
            subscriptions: new StripeSubscriptions([new StripeSubscription(
                "sub_1RoVRTPOQ7ui3NRxYjA12pjl",
                "price_1RoVMePOQ7ui3NRxAQv5Jtpc",
                "active"
            )]),
            guildMemberships: new GuildMemberships([$guildMembership]),
            createdAt: new \DateTimeImmutable(),
            discordUserId: DiscordIdMother::create(),
        );
    }
}
