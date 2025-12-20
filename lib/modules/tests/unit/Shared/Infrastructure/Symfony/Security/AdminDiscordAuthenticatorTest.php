<?php
declare(strict_types=1);

namespace Tests\Unit\App\Shared\Infrastructure\Symfony\Security;

use App\Admin\Application\Discord\AdminDiscordUserConnector;
use App\Frontend\Domain\Discord\DiscordId;
use App\Shared\Infrastructure\Symfony\Security\AdminDiscordAuthenticator;
use KnpU\OAuth2ClientBundle\Client\Provider\DiscordClient;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Tests\Doubles\App\Admin\Domain\User\UserMother;
use Tests\Doubles\App\Admin\Infrastructure\Persistence\InMemory\User\InMemoryTenantRepository;
use Tests\Doubles\App\Admin\Infrastructure\Persistence\InMemory\User\InMemoryUserRepository;
use Wohali\OAuth2\Client\Provider\DiscordResourceOwner;

#[CoversClass(AdminDiscordAuthenticator::class)]
final class AdminDiscordAuthenticatorTest extends TestCase
{
    private InMemoryUserRepository $userRepository;
    private DiscordClient $discordOauthClient;
    private string $discordId = "123456789012345678";

    public function setUp(): void
    {
        parent::setUp();
        $this->userRepository = new InMemoryUserRepository();
        $this->tenantRepository = new InMemoryTenantRepository();
        $this->discordOauthClient = $this->createMock(DiscordClient::class);
    }

    public function testAuthenticateCreatesANewUser(): void
    {
        $authenticator = new AdminDiscordAuthenticator(
            $this->discordOauthClient,
            $this->createMock(RouterInterface::class),
            new AdminDiscordUserConnector($this->userRepository, $this->tenantRepository),
        );

        $accessToken = new AccessToken([
            'access_token' => 'myaccesstoken',
            'refresh_token' => 'myrefreshtoken',
            'expires' => time() + 3600,
            'token_type' => 'Bearer',
            'scope' => 'identify email',
        ]);
        $this->discordOauthClient
            ->method('getAccessToken')
            ->willReturn($accessToken);

        $discordUserData = $this->getDiscordUserData($this->discordId);

        $this->discordOauthClient
            ->method('fetchUserFromToken')
            ->willReturn($discordUserData);

        $request = new Request();
        $passport = $authenticator->authenticate($request);

        $passportUser = $passport->getUser();

        $persistedUser = $this->userRepository->findOneByDiscordId(new DiscordId($this->discordId));
        $this->assertEquals($passportUser->getDiscordId()->value(), $persistedUser->getDiscordId()->value());
        $this->assertEquals('myaccesstoken', $passportUser->getAccessToken()->accessToken);
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

