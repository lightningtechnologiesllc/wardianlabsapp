<?php
declare(strict_types=1);

namespace App\Admin\Infrastructure\Persistence\Doctrine\Tenant;

use App\Admin\Domain\Tenant\TenantPriceToRolesMapping;
use App\Admin\Domain\Tenant\TenantPriceToRolesMappingRepository;
use App\Admin\Domain\Tenant\TenantPriceToRolesMappings;
use App\Frontend\Domain\Discord\GuildId;
use App\Shared\Domain\Tenant\TenantId;
use App\Shared\Infrastructure\Persistence\Doctrine\DoctrineRepository;

class DoctrineTenantPriceToRolesMappingRepository extends DoctrineRepository implements TenantPriceToRolesMappingRepository
{
    public function save(TenantPriceToRolesMapping $mapping): void
    {
        if (!$this->findByTenant($mapping->getTenantId())->isEmpty()) {
            $this->update($mapping);
            return;
        }

        $this->persist($this->toDoctrine($mapping));
    }

    public function update(TenantPriceToRolesMapping $mapping): void
    {
        $existingMapping = $this->getRepository()->findOneBy(['tenantId' => $mapping->getTenantId()]);

        if (null === $existingMapping) {
            throw new \RuntimeException('Mapping does not exist for tenant: ' . $mapping->getTenantId()->value());
        }

        $existingMapping->setPricesToRolesMapping($mapping->getPricesToRolesMapping());

        $this->persist($existingMapping);
    }

    public function findByTenant(TenantId $tenantId): TenantPriceToRolesMappings
    {
        $this->entityManager()->clear();
        $doctrineMappings = $this->getRepository()->findBy(['tenantId' => $tenantId]);

        if (empty($doctrineMappings)) {
            return new TenantPriceToRolesMappings();
        }

        $mappings = [];
        foreach($doctrineMappings as $doctrineMapping) {
            $mappings[] = $this->toDomain($doctrineMapping);
        }

        return new TenantPriceToRolesMappings($mappings);
    }

    public function findAll(): TenantPriceToRolesMappings
    {
        $this->entityManager()->clear();
        $doctrineMappings = $this->getRepository()->findAll();

        if (empty($doctrineMappings)) {
            return new TenantPriceToRolesMappings();
        }

        $mappings = [];
        foreach($doctrineMappings as $doctrineMapping) {
            $mappings[] = $this->toDomain($doctrineMapping);
        }

        return new TenantPriceToRolesMappings($mappings);
    }

    private function getRepository(): \Doctrine\ORM\EntityRepository
    {
        return $this->repository(DoctrineTenantPriceToRolesMapping::class);
    }

    private function toDomain(DoctrineTenantPriceToRolesMapping $mapping): TenantPriceToRolesMapping
    {
        return new TenantPriceToRolesMapping(
            tenantId: new TenantId($mapping->getTenantId()),
            guildId: new GuildId($mapping->getGuildId()),
            pricesToRolesMapping: $mapping->getPricesToRolesMapping(),
        );
    }

    private function toDoctrine(TenantPriceToRolesMapping $mapping): DoctrineTenantPriceToRolesMapping
    {
        return new DoctrineTenantPriceToRolesMapping(
            tenantId: $mapping->getTenantId()->value(),
            guildId: $mapping->getGuildId()->value(),
            pricesToRolesMapping: $mapping->getPricesToRolesMapping(),
        );
    }
}
