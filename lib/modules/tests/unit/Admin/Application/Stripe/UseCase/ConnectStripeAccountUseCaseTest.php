<?php
declare(strict_types=1);

namespace Tests\Unit\App\Admin\Application\Stripe\UseCase;

use App\Admin\Application\Stripe\UseCase\ConnectStripeAccountUseCase;
use App\Shared\Domain\Stripe\StripeAccountRepository;
use App\Shared\Domain\Stripe\StripeClient;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Doubles\App\Shared\Domain\Stripe\InMemoryStripeAccountRepository;
use Tests\Doubles\App\Shared\Domain\Stripe\StripeAccessTokenMother;
use Tests\Doubles\App\Shared\Domain\Stripe\StripeAccountMother;
use Tests\Doubles\App\Shared\Domain\Stripe\StripeProviderAccountMother;
use Tests\Doubles\App\Shared\Domain\Tenant\TenantIdMother;

#[CoversClass(ConnectStripeAccountUseCase::class)]
final class ConnectStripeAccountUseCaseTest extends TestCase
{
    private StripeClient $stripeClient;
    private StripeAccountRepository $accountRepository;
    private $useCase;

    public function setUp(): void
    {
        parent::setUp();

        $this->stripeClient = $this->createMock(StripeClient::class);
        $this->accountRepository = new InMemoryStripeAccountRepository();
        $this->useCase = new ConnectStripeAccountUseCase(
            $this->stripeClient,
            $this->accountRepository
        );
    }

    public function testCreateNewStripeAccount(): void
    {
        $stripeProviderAccount = StripeProviderAccountMother::random();
        $this->stripeClient->method('retrieveAccount')
            ->willReturn($stripeProviderAccount);
        $accessToken = StripeAccessTokenMother::random();

        $tenantId = TenantIdMother::create();
        ($this->useCase)($accessToken, $tenantId);

        $foundAccounts = $this->accountRepository->findByTenantId($tenantId);
        $firstFoundAccount = $foundAccounts->first();
        $this->assertEquals($firstFoundAccount->getTenantId(), $tenantId);
    }

    public function testUpdateStripeAccountIfAlreadyExists(): void
    {
        $account = StripeAccountMother::random();
        $this->accountRepository->save($account);
        $oldAccount = clone $account;

        $stripeProviderAccount = StripeProviderAccountMother::random();
        $stripeProviderAccount->stripeProviderAccountId = $account->getStripeProviderAccountId();

        $this->stripeClient->method('retrieveAccount')
            ->willReturn($stripeProviderAccount);
        $accessToken = StripeAccessTokenMother::random();

        ($this->useCase)($accessToken, $account->getTenantId());

        $foundAccounts = $this->accountRepository->findByTenantId($account->getTenantId());
        $firstFoundAccount = $foundAccounts->first();
        $this->assertEquals($firstFoundAccount->getTenantId(), $oldAccount->getTenantId());
        $this->assertEquals($firstFoundAccount->getAccountId(), $oldAccount->getAccountId());
        $this->assertEquals($firstFoundAccount->getStripeProviderAccountId(), $oldAccount->getStripeProviderAccountId());
        $this->assertNotEquals($firstFoundAccount->getAccessToken()->toArray(), $oldAccount->getAccessToken()->toArray());
        $this->assertEquals($firstFoundAccount->getDisplayName(), $oldAccount->getDisplayName());
    }
}
