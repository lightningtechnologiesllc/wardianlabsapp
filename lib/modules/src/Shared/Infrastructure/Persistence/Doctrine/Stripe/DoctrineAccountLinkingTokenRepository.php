<?php
declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine\Stripe;

use App\Shared\Domain\Stripe\AccountLinkingToken;
use App\Shared\Domain\Stripe\AccountLinkingTokenRepository;
use App\Shared\Infrastructure\Persistence\Doctrine\DoctrineRepository;
use Doctrine\ORM\EntityRepository;

class DoctrineAccountLinkingTokenRepository extends DoctrineRepository implements AccountLinkingTokenRepository
{
    public function save(AccountLinkingToken $token): void
    {
        $this->entityManager()->clear();
        $foundToken = $this->getRepository()->findOneBy(['linkingToken' => $token->getLinkingToken()]);

        if (null === $foundToken) {
            $this->persist(DoctrineAccountLinkingToken::fromDomain($token));
            return;
        }

        $foundToken->updateFromDomain($token);
        $this->persist($foundToken);
    }

    public function findByLinkingToken(string $linkingToken): ?AccountLinkingToken
    {
        $this->entityManager()->clear();
        $token = $this->getRepository()->findOneBy(['linkingToken' => $linkingToken]);

        if (null === $token) {
            return null;
        }

        return $token->toDomain();
    }

    public function findByStripeSubscriptionId(string $stripeSubscriptionId): ?AccountLinkingToken
    {
        $this->entityManager()->clear();
        $token = $this->getRepository()->findOneBy(['stripeSubscriptionId' => $stripeSubscriptionId]);

        if (null === $token) {
            return null;
        }

        return $token->toDomain();
    }

    public function findActiveByCustomerEmail(string $email): array
    {
        $this->entityManager()->clear();
        $tokens = $this->getRepository()->findBy(['customerEmail' => $email]);

        return array_map(
            fn(DoctrineAccountLinkingToken $token) => $token->toDomain(),
            $tokens
        );
    }

    private function getRepository(): EntityRepository
    {
        return $this->repository(DoctrineAccountLinkingToken::class);
    }
}
