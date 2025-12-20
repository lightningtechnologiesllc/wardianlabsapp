<?php
declare(strict_types=1);

namespace App\Admin\Infrastructure\Persistence\Doctrine\Tenant;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'tenant_price_to_roles_mapping')]
#[ORM\UniqueConstraint(name: "tenant_and_guild", columns: ["tenant_id", "guild_id"])]
class DoctrineTenantPriceToRolesMapping
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id;

    public function __construct(
        #[ORM\Column]
        private string $tenantId,
        #[ORM\Column]
        private string $guildId,
        #[ORM\Column]
        private array $pricesToRolesMapping,
    )
    {}

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function getGuildId(): string
    {
        return $this->guildId;
    }

    public function getPricesToRolesMapping(): array
    {
        return $this->pricesToRolesMapping;
    }

    public function setPricesToRolesMapping(array $pricesToRolesMapping): void
    {
        $this->pricesToRolesMapping = $pricesToRolesMapping;
    }
}
