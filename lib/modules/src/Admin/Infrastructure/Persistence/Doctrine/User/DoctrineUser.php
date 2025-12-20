<?php
declare(strict_types=1);

namespace App\Admin\Infrastructure\Persistence\Doctrine\User;

use App\Admin\Infrastructure\Persistence\Doctrine\Tenant\DoctrineTenant;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'users')]
class DoctrineUser
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id;

    public function __construct(
        #[ORM\Column(unique: true)]
        public string $userId,
        #[ORM\Column()]
        public string $discordUserId,
        #[ORM\Column()]
        public string $username,
        #[ORM\Column()]
        public string $globalName,
        #[ORM\Column()]
        public string $email,
        #[ORM\Column()]
        public string $avatar,
        #[ORM\Column()]
        public string $accessToken,
        #[ORM\Column()]
        public string $refreshToken,
        #[ORM\Column(type: Types::BIGINT)]
        public int $expiresOn,
        #[ORM\Column()]
        public string $scope,
        #[ORM\Column()]
        public string $tokenType,

        #[ORM\OneToMany(targetEntity: DoctrineTenant::class, mappedBy: 'owner')]
        public $tenants = [],

        #[ORM\Column(name: 'platform_subscription_id', nullable: true)]
        public ?string $platformSubscriptionId = null,
        #[ORM\Column(name: 'platform_plan_id', nullable: true)]
        public ?string $platformPlanId = null,
        #[ORM\Column(name: 'platform_subscription_status', nullable: true)]
        public ?string $platformSubscriptionStatus = null,
        #[ORM\Column(name: 'platform_subscription_expires_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
        public ?\DateTimeImmutable $platformSubscriptionExpiresAt = null,
    )
    {}
}
