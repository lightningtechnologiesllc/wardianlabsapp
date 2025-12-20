<?php
declare(strict_types=1);

namespace Tests\E2E\App\Admin;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Depends;
use Tests\E2E\App\PantherTestCase;

#[CoversNothing]
final class AdminLoginTest extends PantherTestCase
{
    private string $discordTestEmail;
    private string $discordTestPassword;

    protected function setUp(): void
    {
        parent::setUp();

        $this->discordTestEmail = $_ENV['E2E_DISCORD_TEST_EMAIL'] ?? '';
        $this->discordTestPassword = $_ENV['E2E_DISCORD_TEST_PASSWORD'] ?? '';

        if (empty($this->discordTestEmail) || empty($this->discordTestPassword)) {
            $this->markTestSkipped('Discord test credentials not configured. Set E2E_DISCORD_TEST_EMAIL and E2E_DISCORD_TEST_PASSWORD env vars.');
        }
    }

    public function testAdminLoginViaDiscord(): void
    {
        // Visit admin home - should redirect to login
        $this->client->request('GET', '/admin');

        // Take screenshot of login page
        $this->takeScreenshot('admin-01-login-page.png');

        // Click "Login with Discord" button
        $this->client->clickLink('Continue with Discord');

        // Should redirect to Discord OAuth
        $this->takeScreenshot('admin-02-discord-oauth.png');

        // Fill Discord login form
        $this->client->waitFor('input[name="email"]', 10);
        $this->client->findElement(\Facebook\WebDriver\WebDriverBy::name('email'))
            ->sendKeys($this->discordTestEmail);

        $this->client->findElement(\Facebook\WebDriver\WebDriverBy::name('password'))
            ->sendKeys($this->discordTestPassword);

        $this->takeScreenshot('admin-03-discord-credentials.png');

        // Submit login form
        $this->client->findElement(\Facebook\WebDriver\WebDriverBy::cssSelector('button[type="submit"]'))
            ->click();

        // Wait for authorization page or redirect
        sleep(3);
        $this->takeScreenshot('admin-04-discord-authorize.png');

        // If authorization page shows, scroll the modal and click Authorize
        try {
            // Discord requires scrolling within the modal to enable the Authorize button
            // The scrollable container has class containing 'scrollerBase'
            $this->client->executeScript("
                var scrollContainer = document.querySelector('[class*=\"scrollerBase\"]');
                if (scrollContainer) {
                    scrollContainer.scrollTop = scrollContainer.scrollHeight;
                }
            ");
            sleep(1);

            $this->takeScreenshot('admin-04b-after-scroll.png');

            // Wait for the Authorize button to become enabled
            $this->client->waitFor('button.primary_a22cb0:not([disabled])', 5);

            $authorizeButton = $this->client->findElement(
                \Facebook\WebDriver\WebDriverBy::cssSelector('button.primary_a22cb0')
            );
            $authorizeButton->click();
            sleep(2);
        } catch (\Exception $e) {
            // Already authorized, will redirect automatically
        }

        $this->takeScreenshot('admin-05-after-auth.png');

        // Should be redirected to admin home or subscription page
        $currentUrl = $this->client->getCurrentURL();

//        $this->assertEquals("/admin", $currentUrl);
        $this->assertStringContainsString('/admin', $currentUrl, $currentUrl);
    }

    #[Depends('testAdminLoginViaDiscord')]
    public function testAdminAccessTenantSettings(): void
    {
        // Session should be preserved from testAdminLoginViaDiscord
        // Navigate to tenant settings (if user has active subscription)
        $this->client->request('GET', '/admin/discord/guilds');

        $this->takeScreenshot('admin-06-guilds-list.png');

        // Verify we can see the guilds list
        $pageSource = $this->client->getPageSource();

        // Should either show guilds or subscription required message
        $this->assertTrue(
            str_contains($pageSource, 'Discord Servers') ||
            str_contains($pageSource, 'subscription')
        );
    }
}
