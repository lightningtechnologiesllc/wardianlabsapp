<?php
declare(strict_types=1);

namespace App\Admin\Domain\Tenant;

use App\Frontend\Domain\Discord\GuildId;
use App\Shared\Domain\Tenant\TenantId;

final class TenantPriceToRolesMapping
{
    public function __construct(
        private readonly TenantId $tenantId,
        private readonly GuildId  $guildId,
        private array             $pricesToRolesMapping,
    )
    {
    }

    public function getTenantId(): TenantId
    {
        return $this->tenantId;
    }

    public function getGuildId(): GuildId
    {
        return $this->guildId;
    }

    public function getPricesToRolesMapping(): array
    {
        return $this->pricesToRolesMapping;
    }

    public function getRolesPerPrice(string $price): array
    {
        return $this->pricesToRolesMapping[$price] ?? [];
    }

    public function doesRoleExist(string $price, string $roleId): bool
    {
        foreach ($this->pricesToRolesMapping as $mappingPrice => $roles) {
            if ($mappingPrice !== $price) {
                continue;
            }

            if (in_array($roleId, $roles, true)) {
                return true;
            }
        }

        return false;
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId->value(),
            'guild_id' => $this->guildId->value(),
            'prices_to_roles_mapping' => $this->pricesToRolesMapping,
        ];
    }

    public static function fromArray(mixed $data): self
    {
        return new self(
            new TenantId($data['tenant_id']),
            new GuildId($data['guild_id']),
            $data['prices_to_roles_mapping'],
        );
    }

}
