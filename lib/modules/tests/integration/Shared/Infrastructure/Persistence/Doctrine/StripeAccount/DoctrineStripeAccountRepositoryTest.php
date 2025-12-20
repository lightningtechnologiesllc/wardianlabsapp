<?php
declare(strict_types=1);

namespace Tests\Integration\App\Shared\Infrastructure\Persistence\Doctrine\StripeAccount;

use App\Shared\Domain\Stripe\StripeAccessToken;
use App\Shared\Domain\Stripe\StripeAccountId;
use App\Shared\Domain\Stripe\StripeAccountRepository;
use App\Shared\Infrastructure\Persistence\Doctrine\Stripe\DoctrineStripeAccountRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Doubles\App\Shared\Domain\Stripe\StripeAccessTokenMother;
use Tests\Doubles\App\Shared\Domain\Stripe\StripeAccountMother;
use Tests\Integration\App\Shared\Infrastructure\IntegrationTestCase;

#[CoversClass(DoctrineStripeAccountRepository::class)]
final class DoctrineStripeAccountRepositoryTest extends IntegrationTestCase
{
    public function testDoesNotFindNonExistentAccount(): void
    {
        $repository = $this->getRepository();
        $accountId = StripeAccountId::random();

        $account = $repository->find($accountId);

        $this->assertNull($account);
    }

    public function testFindAccountById(): void
    {
        $repository = $this->getRepository();
        $account = StripeAccountMother::random();

        $repository->save($account);

        $this->assertAccountExists($account->getAccountId());
    }

    public function testDeleteAccount(): void
    {
        $this->markTestIncomplete("Delete is not working. It's not actually deleting it");
        $repository = $this->getRepository();
        $account = StripeAccountMother::random();
        $repository->save($account);
        $this->assertAccountExists($account->getAccountId());

        $repository->delete($account);

        $this->assertNull($repository->find($account->getAccountId()));
    }

    public function testSaveAccessToken(): void
    {
        $repository = $this->getRepository();

        $account = StripeAccountMother::random();
        $repository->save($account);

        $oldAccessToken = $account->getAccessToken();

        $refreshedToken = StripeAccessTokenMother::random();

        $this->getRepository()->saveAccessToken($account->getAccountId(), $refreshedToken);

        $foundAccount = $this->getRepository()->find($account->getAccountId());
        $this->assertNotEquals($oldAccessToken->toArray(), $foundAccount->getAccessToken()->toArray());
    }

    private function getRepository(): DoctrineStripeAccountRepository
    {
        return $this->service(StripeAccountRepository::class);
    }

    private function assertAccountExists(StripeAccountId $id)
    {
        $found = $this->getRepository()->find($id);
        $this->assertNotNull($found);
        $this->assertEquals($found->getAccountId(), $id);
    }
}
