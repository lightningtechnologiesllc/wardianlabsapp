<?php
declare(strict_types=1);

namespace Tests\Integration\App\Frontend\Infrastructure\Provider;

use App\Frontend\Domain\Providers\Exception\UnknownGuildIdException;
use App\Frontend\Infrastructure\Provider\ApiDiscordBotManagerProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Integration\App\Shared\Infrastructure\IntegrationTestCase;

#[CoversClass(ApiDiscordBotManagerProvider::class)]
final class ApiDiscordUserManagerProviderTest extends IntegrationTestCase
{
    public function testEverythingWorks(): void
    {
        $this->markTestSkipped("This test is skipped because it requires a valid Discord token and user ID.");

        $this->getProvider()->addRolesToUser("1051640745611755640",  "862650794549575682", ["1051641411503673486"]);
    }

    public function testInvalidGuildIdThrowsException(): void
    {
        $this->expectException(UnknownGuildIdException::class);
        $this->expectExceptionMessage("Unknown guild with ID \"123456\"");

        $this->getProvider()->addRolesToUser("123456", "862650794549575682", ["1051641411503673486"]);
    }

    public function testInvalidUserIdThrowsException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Unknown user with ID \"123456\".");

        $this->getProvider()->addRolesToUser("1051640745611755640", "123456", ["1051641411503673486"]);
    }

    public function testInvalidRoleIdThrowsException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Unknown role with ID \"123456\".");

        $this->getProvider()->addRolesToUser("1051640745611755640", "862650794549575682", ["123456"]);
    }

    private function getProvider(): ApiDiscordBotManagerProvider
    {
        return $this->service(ApiDiscordBotManagerProvider::class);
    }
}
