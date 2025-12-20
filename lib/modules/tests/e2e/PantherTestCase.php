<?php

declare(strict_types=1);

namespace Tests\E2E\App;

use PHPUnit\Framework\Attributes\CoversNothing;
use Symfony\Component\Panther\Client as PantherClient;
use Symfony\Component\Panther\PantherTestCase as PantherTestCaseBase;

/**
 * @internal
 */
#[CoversNothing]
class PantherTestCase extends PantherTestCaseBase
{
    protected PantherClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        $kernel = static::createKernel();
        $this->client = static::createPantherClient();
    }

    protected function takeScreenshot(string $name): void
    {
        $this->client->takeScreenshot(sprintf('/app/var/screenshots/%s', $name));
    }
}
