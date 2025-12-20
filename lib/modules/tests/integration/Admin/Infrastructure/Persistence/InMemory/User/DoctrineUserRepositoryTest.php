<?php
declare(strict_types=1);

namespace Tests\Integration\App\Admin\Infrastructure\Persistence\InMemory\User;

use App\Admin\Domain\User\UserId;
use App\Admin\Domain\User\UserRepository;
use App\Admin\Infrastructure\Persistence\Doctrine\User\DoctrineUserRepository;
use App\Frontend\Domain\Discord\DiscordId;
use App\Shared\Domain\Stripe\StripeAccountId;
use App\Shared\Domain\Stripe\StripeAccountRepository;
use App\Shared\Infrastructure\Persistence\Doctrine\Stripe\DoctrineStripeAccountRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Doubles\App\Admin\Domain\User\UserMother;
use Tests\Doubles\App\Shared\Domain\Stripe\StripeAccountMother;
use Tests\Integration\App\Shared\Infrastructure\IntegrationTestCase;

#[CoversClass(DoctrineUserRepository::class)]
final class DoctrineUserRepositoryTest extends IntegrationTestCase
{
    public function testWeCanFindASavedUser(): void
    {
        $repository = $this->getRepository();
        $user = UserMother::random();

        $repository->save($user);

        $this->assertUserExists($user->getDiscordId());
    }

    private function getRepository(): DoctrineUserRepository
    {
        return $this->service(UserRepository::class);
    }

    private function assertUserExists(DiscordId $id)
    {
        $found = $this->getRepository()->findOneByDiscordId($id);
        $this->assertNotNull($found);
        $this->assertEquals($found->getDiscordId(), $id);
    }
}
