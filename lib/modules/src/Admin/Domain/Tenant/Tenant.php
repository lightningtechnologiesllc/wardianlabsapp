<?php
declare(strict_types=1);

namespace App\Admin\Domain\Tenant;

use App\Admin\Domain\User\User;
use App\Shared\Domain\Tenant\TenantId;

final class Tenant
{
    private User $owner;

    public function __construct(
        private \App\Shared\Domain\Tenant\TenantId $id,
        private string $name,
        private string $subdomain,
        #[\SensitiveParameter] private string $emailDSN,
        private string $emailFromAddress,
    )
    {
    }

    public function getId(): TenantId
    {
        return $this->id;
    }

    public function setOwner(User $owner): void
    {
        $this->owner = $owner;
    }

    public function getOwner(): User
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

    public function getEmailDSN(): string
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

    public function toArray(): array
    {
        return [
            'id' => $this->id->value(),
            'owner' => $this->owner->toArray(),
            'name' => $this->name,
            'subdomain' => $this->subdomain,
            'email_dsn' => $this->emailDSN,
            'email_from_address' => $this->emailFromAddress,
        ];
    }

    public static function fromArray(array $data): self
    {
        $tenant = new self(
            new TenantId($data['id']),
            $data['name'],
            $data['subdomain'],
            $data['email_dsn'],
            $data['email_from_address'],
        );
        $tenant->setOwner(User::fromArray($data['owner']));
        return $tenant;
    }
}
