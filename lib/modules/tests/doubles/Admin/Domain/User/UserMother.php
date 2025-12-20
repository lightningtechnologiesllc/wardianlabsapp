<?php
declare(strict_types=1);

namespace Tests\Doubles\App\Admin\Domain\User;

use App\Admin\Domain\Tenant\Tenants;
use App\Admin\Domain\User\User;
use App\Frontend\Domain\Discord\DiscordId;
use App\Shared\Domain\Discord\DiscordAccessToken;
use Tests\Doubles\App\Frontend\Domain\DiscordId\DiscordIdMother;

final class UserMother
{
    public static function random(): User
    {
        return new User(
            userId: UserIdMother::create(),
            discordId: DiscordIdMother::create(),
            username: "TestUsername",
            globalName: "Test User",
            email: "foo@bar.com",
            avatar: "avatar_hash",
            accessToken: new DiscordAccessToken(
                accessToken: "access_". bin2hex(random_bytes(16)),
                refreshToken: "refresh_" . bin2hex(random_bytes(16)),
                expiresOn: time() + 3600,
                scope: "identify email",
                tokenType: "Bearer"
            ),
            tenants: new Tenants([]),
        );
    }

    public static function randomWithDiscordId(DiscordId $discordId): User
    {
        return new User(
            userId: UserIdMother::create(),
            discordId: $discordId,
            username: "TestUsername",
            globalName: "Test User",
            email: "foo@bar.com",
            avatar: "avatar_hash",
            accessToken: new DiscordAccessToken(
                accessToken: "access_". bin2hex(random_bytes(16)),
                refreshToken: "refresh_" . bin2hex(random_bytes(16)),
                expiresOn: time() + 3600,
                scope: "identify email",
                tokenType: "Bearer"
            ),
            tenants: new Tenants([]),
        );
    }

    public static function createWithPlatformSubscription(
        ?\App\Admin\Domain\User\PlatformSubscription $platformSubscription
    ): User {
        return new User(
            userId: UserIdMother::create(),
            discordId: DiscordIdMother::create(),
            username: "TestUsername",
            globalName: "Test User",
            email: "foo@bar.com",
            avatar: "avatar_hash",
            accessToken: new DiscordAccessToken(
                accessToken: "access_". bin2hex(random_bytes(16)),
                refreshToken: "refresh_" . bin2hex(random_bytes(16)),
                expiresOn: time() + 3600,
                scope: "identify email",
                tokenType: "Bearer"
            ),
            tenants: new Tenants([]),
            platformSubscription: $platformSubscription,
        );
    }

    public static function randomWithUsername(string $username): User
    {
        return new User(
            userId: UserIdMother::create(),
            discordId: DiscordIdMother::create(),
            username: $username,
            globalName: "Test User",
            email: "foo@bar.com",
            avatar: "avatar_hash",
            accessToken: new DiscordAccessToken(
                accessToken: "access_". bin2hex(random_bytes(16)),
                refreshToken: "refresh_" . bin2hex(random_bytes(16)),
                expiresOn: time() + 3600,
                scope: "identify email",
                tokenType: "Bearer"
            ),
            tenants: new Tenants([]),
        );
    }
}
