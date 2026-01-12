<?php
declare(strict_types=1);

namespace App\Admin\Infrastructure\Persistence\Doctrine\Stripe;

use App\Admin\Domain\Stripe\PendingStripeInstallation;
use App\Admin\Domain\Stripe\PendingStripeInstallationRepository;
use App\Shared\Infrastructure\Persistence\Doctrine\DoctrineRepository;
use Doctrine\ORM\EntityRepository;

class DoctrinePendingStripeInstallationRepository extends DoctrineRepository implements PendingStripeInstallationRepository
{
    public function save(PendingStripeInstallation $installation): void
    {
        $this->entityManager()->clear();
        $found = $this->getRepository()->findOneBy(['linkingToken' => $installation->getLinkingToken()]);

        if (null === $found) {
            $this->persist(DoctrinePendingStripeInstallation::fromDomain($installation));
            return;
        }

        $found->updateFromDomain($installation);
        $this->persist($found);
    }

    public function findByLinkingToken(string $linkingToken): ?PendingStripeInstallation
    {
        $this->entityManager()->clear();
        $installation = $this->getRepository()->findOneBy(['linkingToken' => $linkingToken]);

        if (null === $installation) {
            return null;
        }

        return $installation->toDomain();
    }

    public function findByStripeUserId(string $stripeUserId): ?PendingStripeInstallation
    {
        $this->entityManager()->clear();
        $installation = $this->getRepository()->findOneBy(['stripeUserId' => $stripeUserId]);

        if (null === $installation) {
            return null;
        }

        return $installation->toDomain();
    }

    private function getRepository(): EntityRepository
    {
        return $this->repository(DoctrinePendingStripeInstallation::class);
    }
}
