<?php
declare(strict_types=1);

namespace App\Admin\Infrastructure\Persistence\Doctrine\Tenant;

use App\Admin\Domain\Tenant\Tenant;
use App\Shared\Domain\Tenant\TenantId;
use App\Admin\Domain\Tenant\TenantRepository;
use App\Admin\Infrastructure\Persistence\Doctrine\User\DoctrineUser;
use App\Admin\Infrastructure\Persistence\Doctrine\User\DoctrineUserRepository;
use App\Frontend\Domain\Discord\DiscordId;
use App\Shared\Infrastructure\Persistence\Doctrine\DoctrineRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class DoctrineTenantRepository extends DoctrineRepository implements TenantRepository
{
    public function __construct(
        EntityManagerInterface $entityManager,
        private readonly DoctrineUserRepository $userRepository,
    )
    {
        parent::__construct($entityManager);
    }

    public function save(Tenant $tenant): void
    {
        $this->entityManager()->clear();
        $foundTenant = $this->getRepository()->findOneBy(['tenantId' => $tenant->getId()->value()]);

        if (null === $foundTenant) {
            $this->persist($this->toDoctrine($tenant));
            return;
        }

        $foundTenant->updateFromDomain($tenant);
        $this->persist($foundTenant);
    }

    public function findOneBySubdomain(string $subdomain): ?Tenant
    {
        $this->entityManager()->clear();
        $tenant = $this->getRepository()->findOneBy(['subdomain' => $subdomain]);

        if (null === $tenant) {
            return null;
        }

        return $this->toDomain($tenant);
    }

    public function findById(TenantId $tenantId): ?Tenant
    {
        $this->entityManager()->clear();
        $tenant = $this->getRepository()->findOneBy(['tenantId' => $tenantId->value()]);

        if (null === $tenant) {
            return null;
        }

        return $this->toDomain($tenant);
    }

    private function toDomain(DoctrineTenant $tenant): Tenant
    {
        $domainTenant = new Tenant(
            id: new TenantId($tenant->getTenantId()),
            name: $tenant->getName(),
            subdomain: $tenant->getSubdomain(),
            emailDSN: $tenant->getEmailDSN(),
            emailFromAddress: $tenant->getEmailFromAddress(),
        );
        if ($tenant->getOwner() !== null) {
            $owner = $this->userRepository->findOneByDiscordId(new DiscordId($tenant->getOwner()->discordUserId));
            $owner->addTenant($domainTenant);
        }
        return $domainTenant;
    }

    private function toDoctrine(Tenant $tenant): DoctrineTenant
    {
        $user = $this->repository(DoctrineUser::class)
            ->findOneBy(["userId" => $tenant->getOwner()?->id()->value()]);

        $doctrineTenant = new DoctrineTenant(
            tenantId: $tenant->getId()->value(),
            name: $tenant->getName(),
            subdomain: $tenant->getSubdomain(),
            emailDSN: $tenant->getEmailDSN(),
            emailFromAddress: $tenant->getEmailFromAddress(),
        );

        if ($user) {
            $doctrineTenant->setOwner($user);
        }

        return $doctrineTenant;
    }

    private function getRepository(): EntityRepository
    {
        return $this->repository(DoctrineTenant::class);
    }
}
