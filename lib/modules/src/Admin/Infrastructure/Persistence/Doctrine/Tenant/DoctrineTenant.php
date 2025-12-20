<?php
declare(strict_types=1);

namespace App\Admin\Infrastructure\Persistence\Doctrine\Tenant;

use App\Admin\Domain\Tenant\Tenant;
use App\Admin\Infrastructure\Persistence\Doctrine\User\DoctrineUser;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'tenants')]
class DoctrineTenant
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id;
    #[ORM\ManyToOne(targetEntity: DoctrineUser::class, inversedBy: 'tenants')]
    #[ORM\JoinColumn(name: "owner_id", referencedColumnName: "id")]
    private ?DoctrineUser $owner = null;

    public function __construct(
        #[ORM\Column(unique: true)]
        private string $tenantId,
        #[ORM\Column]
        private string $name,
        #[ORM\Column(unique: true)]
        private string $subdomain,
        #[ORM\Column]
        private string $emailDSN,
        #[ORM\Column()]
        private string $emailFromAddress,
    )
    {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function setOwner(DoctrineUser $owner): void
    {
        $this->owner = $owner;
    }

    public function getOwner(): ?DoctrineUser
    {
        return $this->owner;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSubdomain(): string
    {
        return $this->subdomain;
    }

    public function getEmailDSN(): ?string
    {
        return $this->emailDSN;
    }

    public function getEmailFromAddress(): string
    {
        return $this->emailFromAddress;
    }

    public function updateName(string $name): void
    {
        $this->name = $name;
    }

    public function updateSubdomain(string $subdomain): void
    {
        $this->subdomain = $subdomain;
    }

    public function updateEmailDSN(string $emailDSN): void
    {
        $this->emailDSN = $emailDSN;
    }

    public function updateEmailFromAddress(string $emailFromAddress): void
    {
        $this->emailFromAddress = $emailFromAddress;
    }

    public function updateFromDomain(Tenant $tenant): void
    {
        $this->updateName($tenant->getName());
        $this->updateSubdomain($tenant->getSubdomain());
        $this->updateEmailDSN($tenant->getEmailDSN());
        $this->updateEmailFromAddress($tenant->getEmailFromAddress());
    }

    public function toDomain(): Tenant
    {
        return new Tenant(
            id: new \App\Shared\Domain\Tenant\TenantId($this->getTenantId()),
            name: $this->getName(),
            subdomain: $this->getSubdomain(),
            emailDSN: $this->getEmailDSN(),
            emailFromAddress: $this->getEmailFromAddress(),
        );
    }
}
