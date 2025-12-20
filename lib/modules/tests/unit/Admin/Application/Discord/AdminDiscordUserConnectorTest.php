<?php
declare(strict_types=1);

namespace Tests\Unit\App\Admin\Application\Discord;

use App\Admin\Application\Discord\AdminDiscordUserConnector;
use App\Frontend\Domain\Discord\DiscordId;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Doubles\App\Admin\Domain\User\UserMother;
use Tests\Doubles\App\Admin\Infrastructure\Persistence\InMemory\User\InMemoryTenantRepository;
use Tests\Doubles\App\Admin\Infrastructure\Persistence\InMemory\User\InMemoryUserRepository;
use Wohali\OAuth2\Client\Provider\DiscordResourceOwner;

#[CoversClass(AdminDiscordUserConnector::class)]
final class AdminDiscordUserConnectorTest extends TestCase
{
    private InMemoryUserRepository $userRepository;
    private AdminDiscordUserConnector $connector;
    private string $discordId = "123456789012345678";

    public function setUp(): void
    {
        parent::setUp();

        $this->userRepository = new InMemoryUserRepository();
        $this->tenantRepository = new InMemoryTenantRepository();
        $this->connector = new AdminDiscordUserConnector($this->userRepository, $this->tenantRepository);
    }

    public function testCreatesANewUser(): void
    {
        $discordUserData = $this->getDiscordUserData($this->discordId);

        $accessToken = new AccessToken([
            'access_token' => 'myaccesstoken',
            'refresh_token' => 'myrefreshtoken',
            'expires' => time() + 3600,
            'token_type' => 'Bearer',
            'scope' => 'identify email',
        ]);

        $user = ($this->connector)(new DiscordId($this->discordId), $discordUserData->toArray(), $accessToken);

        $persistedUser = $this->userRepository->findOneByDiscordId($user->getDiscordId());
        $this->assertEquals($user->getDiscordId()->value(), $persistedUser->getDiscordId()->value());
        $this->assertEquals('myaccesstoken', $user->getAccessToken()->accessToken);

    }

    public function testOverwritesAccessTokenIfUserAlreadyExists(): void
    {
        $user = UserMother::randomWithDiscordId(new DiscordId($this->discordId));
        $oldAccessTokenValue = $user->getAccessToken()->accessToken;
        $this->userRepository->save($user);

        $accessToken = new AccessToken([
            'access_token' => 'myaccesstoken',
            'refresh_token' => 'myrefreshtoken',
            'expires' => time() + 3600,
            'token_type' => 'Bearer',
            'scope' => 'identify email',
        ]);

        $discordUserData = $this->getDiscordUserData($this->discordId);

        $user = ($this->connector)(new DiscordId($this->discordId), $discordUserData->toArray(), $accessToken);

        $persistedUser = $this->userRepository->findOneByDiscordId($user->getDiscordId());
        $this->assertEquals($user->getDiscordId()->value(), $persistedUser->getDiscordId()->value());
        $this->assertNotEquals($user->getAccessToken()->accessToken, $oldAccessTokenValue);
        $this->assertEquals($user->getAccessToken()->accessToken, 'myaccesstoken');
    }

    public function testDoesNotCreateATeantIfItAlreadyExists(): void
    {
        $this->markTestIncomplete();
        $user = UserMother::randomWithDiscordId(new DiscordId($this->discordId));
        $this->userRepository->save($user);

    }

    public function getDiscordUserData(string $discordId): DiscordResourceOwner
    {
        return new DiscordResourceOwner([
            "id" => $discordId,
            "username" => "my_username",
            "avatar" => "9f7add91230846211bf5b3dd6cf21abf",
            "discriminator" => "0",
            "public_flags" => 0,
            "flags" => 0,
            "banner" => null,
            "accent_color" => null,
            "global_name" => "Test Global User Name",
            "avatar_decoration_data" => null,
            "collectibles" => null,
            "display_name_styles" => null,
            "banner_color" => null,
            "clan" => null,
            "primary_guild" => null,
            "mfa_enabled" => true,
            "locale" => "en-US",
            "premium_type" => 0,
            "email" => "testuser@gmail.com",
            "verified" => true,
        ]);
    }
}
