<?php
declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine\Stripe;

use App\Shared\Domain\Stripe\StripeProviderAccount;
use App\Shared\Domain\Tenant\TenantId;
use App\Shared\Domain\Stripe\StripeAccessToken;
use App\Shared\Domain\Stripe\StripeAccount;
use App\Shared\Domain\Stripe\StripeAccountId;
use App\Shared\Domain\Stripe\StripeAccountRepository;
use App\Shared\Domain\Stripe\StripeAccounts;
use App\Shared\Infrastructure\Persistence\Doctrine\DoctrineRepository;

class DoctrineStripeAccountRepository extends DoctrineRepository implements StripeAccountRepository
{
    public function find(StripeAccountId $id): ?StripeAccount
    {
        $this->entityManager()->clear();

        $account = $this->getRepository()->findOneBy(['accountId' => $id->value()]);

        if (null === $account) {
            return null;
        }

        return self::toDomain($account);
    }

    public function save(StripeAccount $account): void
    {
        $this->persist($this->toDoctrine($account));
    }

    public function findByTenantId(TenantId $tenantId): StripeAccounts
    {
        $doctrineAccounts = $this->getRepository()->findBy([
            "tenantId" => $tenantId
        ]);

        $stripeAccounts = new StripeAccounts();

        foreach($doctrineAccounts as $doctrineAccount) {
            $stripeAccounts->add(DoctrineStripeAccountRepository::toDomain($doctrineAccount));
        }

        return $stripeAccounts;
    }

    public function findByStripeProviderAccountId(string $stripeProviderAccountId): ?StripeAccount
    {
        $this->entityManager()->clear();

        $account = $this->getRepository()->findOneBy(['stripeProviderAccountId' => $stripeProviderAccountId]);

        if (null === $account) {
            return null;
        }

        return self::toDomain($account);
    }


    public function saveAccessToken(StripeAccountId $accountId, StripeAccessToken $refreshedToken):void
    {
        $account = $this->getRepository()->findOneBy(['accountId' => $accountId->value()]);

        $account->accessToken = $refreshedToken->accessToken;
        $account->refreshToken = $refreshedToken->refreshToken;
        $account->publishableKey = $refreshedToken->publishableKey;
        $account->scope = $refreshedToken->scope;
        $account->livemode = $refreshedToken->livemode;
        $account->tokenType = $refreshedToken->tokenType;

        $this->entityManager()->persist($account);
        $this->entityManager()->flush();
    }

    public function delete(StripeAccount $account): void
    {
        $doctrineAccount = $this->getRepository()->findOneBy(['accountId' => $account->getAccountId()->value()]);
        $this->remove($doctrineAccount);
    }

    public function updateStripeProviderAccount(StripeAccountId $accountId, StripeProviderAccount $stripeProviderAccount): void
    {
        $account = $this->getRepository()->findOneBy(['accountId' => $accountId->value()]);

        $account->stripeProviderAccountId = $stripeProviderAccount->stripeProviderAccountId;
        $account->displayName = $stripeProviderAccount->displayName;
        $account->accessToken = $stripeProviderAccount->accessToken->accessToken;
        $account->refreshToken = $stripeProviderAccount->accessToken->refreshToken;
        $account->publishableKey = $stripeProviderAccount->accessToken->publishableKey;
        $account->scope = $stripeProviderAccount->accessToken->scope;
        $account->livemode = $stripeProviderAccount->accessToken->livemode;
        $account->tokenType = $stripeProviderAccount->accessToken->tokenType;

        $this->entityManager()->flush();
    }

    private function toDoctrine(StripeAccount $account): DoctrineStripeAccount
    {
        return new DoctrineStripeAccount(
            accountId:  $account->getAccountId()->value(),
            tenantId: $account->getTenantId()->value(),
            stripeProviderAccountId: $account->getStripeProviderAccountId(),
            displayName: $account->getDisplayName(),
            stripeUserId: $account->getAccessToken()->stripeUserId,
            accessToken: $account->getAccessToken()->accessToken,
            refreshToken: $account->getAccessToken()->refreshToken,
            publishableKey: $account->getAccessToken()->publishableKey,
            scope: $account->getAccessToken()->scope,
            livemode: $account->getAccessToken()->livemode,
            tokenType: $account->getAccessToken()->tokenType,
            webhookSecret: $account->getWebhookSecret(),
            webhookEndpointId: $account->getWebhookEndpointId(),
        );
    }

    public static function toDomain(DoctrineStripeAccount $doctrineAccount): StripeAccount
    {
        return new StripeAccount(
            accountId: new StripeAccountId($doctrineAccount->accountId),
            tenantId: new TenantId($doctrineAccount->tenantId),
            stripeProviderAccountId: $doctrineAccount->stripeProviderAccountId,
            displayName: $doctrineAccount->displayName,
            accessToken: new \App\Shared\Domain\Stripe\StripeAccessToken(
                accessToken: $doctrineAccount->accessToken,
                refreshToken: $doctrineAccount->refreshToken,
                stripeUserId: $doctrineAccount->stripeUserId,
                publishableKey: $doctrineAccount->publishableKey,
                scope: $doctrineAccount->scope,
                livemode: $doctrineAccount->livemode,
                tokenType: $doctrineAccount->tokenType,
            ),
            webhookSecret: $doctrineAccount->webhookSecret,
            webhookEndpointId: $doctrineAccount->webhookEndpointId,
        );
    }

    public function getRepository(): \Doctrine\ORM\EntityRepository
    {
        return $this->repository(DoctrineStripeAccount::class);
    }
}
