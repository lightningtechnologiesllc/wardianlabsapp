<?php
declare(strict_types=1);

namespace App\Admin\Infrastructure\Persistence\Doctrine\User;

use App\Admin\Domain\User\PendingPlatformSubscription;
use App\Admin\Domain\User\PendingPlatformSubscriptionRepository;
use App\Shared\Infrastructure\Persistence\Doctrine\DoctrineRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class DoctrinePendingPlatformSubscriptionRepository extends DoctrineRepository implements PendingPlatformSubscriptionRepository
{
    public function __construct(
        EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
        parent::__construct($entityManager);
    }

    public function save(PendingPlatformSubscription $pendingSubscription): void
    {
        $this->logger->info('Saving pending subscription', [
            'id' => $pendingSubscription->id()->value(),
            'email' => $pendingSubscription->getCustomerEmail(),
            'coupon' => $pendingSubscription->getCouponCode(),
        ]);

        try {
            $doctrineEntity = $this->toDoctrine($pendingSubscription);

            $this->logger->info('Doctrine entity created', [
                'subscription_id' => $doctrineEntity->getSubscriptionId(),
                'plan_id' => $doctrineEntity->getPlanId(),
                'status' => $doctrineEntity->getStatus(),
            ]);

            $this->entityManager()->persist($doctrineEntity);
            $this->logger->info('Entity added to unit of work');

            $this->entityManager()->flush();
            $this->logger->info('Pending subscription flushed to database');
        } catch (\Exception $e) {
            $this->logger->error('Failed to save pending subscription', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function findByCouponCode(string $couponCode): ?PendingPlatformSubscription
    {
        $this->entityManager()->clear();
        $doctrinePending = $this->getRepository()->findOneBy(['couponCode' => $couponCode]);

        if (null === $doctrinePending) {
            return null;
        }

        return $doctrinePending->toDomain();
    }

    public function findBySubscriptionId(string $subscriptionId): ?PendingPlatformSubscription
    {
        $this->entityManager()->clear();
        $doctrinePending = $this->getRepository()->findOneBy(['subscriptionId' => $subscriptionId]);

        if (null === $doctrinePending) {
            return null;
        }

        return $doctrinePending->toDomain();
    }

    private function toDoctrine(PendingPlatformSubscription $pending): DoctrinePendingPlatformSubscription
    {
        // Clear entity manager to ensure fresh state
        $this->entityManager()->clear();

        $this->logger->info('Looking for existing pending subscription', [
            'pending_subscription_id' => $pending->id()->value(),
        ]);

        // Try to find existing entity
        $doctrinePending = $this->getRepository()->findOneBy([
            'pendingSubscriptionId' => $pending->id()->value()
        ]);

        if (null === $doctrinePending) {
            $this->logger->info('Creating new pending subscription entity');
            // Create new entity
            return new DoctrinePendingPlatformSubscription(
                pendingSubscriptionId: $pending->id()->value(),
                customerEmail: $pending->getCustomerEmail(),
                couponCode: $pending->getCouponCode(),
                subscriptionId: $pending->getSubscription()->getSubscriptionId(),
                planId: $pending->getSubscription()->getPlanId(),
                status: $pending->getSubscription()->getStatus(),
                expiresAt: $pending->getSubscription()->getExpiresAt(),
                redeemed: $pending->isRedeemed(),
                createdAt: $pending->getCreatedAt(),
            );
        }

        // Update existing entity
        $doctrinePending->updateFromDomain($pending);

        return $doctrinePending;
    }

    private function getRepository(): \Doctrine\ORM\EntityRepository
    {
        return $this->repository(DoctrinePendingPlatformSubscription::class);
    }
}
