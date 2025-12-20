<?php
declare(strict_types=1);

namespace App\Frontend\Infrastructure\Persistence\Doctrine\Member;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'members')]
class DoctrineMember
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id;

    public function __construct(
        #[ORM\Column(unique: true)]
        public string $memberId,
        #[ORM\Column()]
        public string $tenantId,
        #[ORM\Column()]
        public string $customerEmail,
        #[ORM\Column()]
        public array $subscriptions,
        #[ORM\Column()]
        public array $guildMemberships,
        #[ORM\Column(type: 'datetime_immutable')]
        public \DateTimeImmutable $createdAt,
        #[ORM\Column(nullable: true)]
        public ?string $discordUserId = null,
        #[ORM\Column(nullable: true)]
        public ?string $linkingToken = null,
        #[ORM\Column(type: 'datetime_immutable', nullable: true)]
        public ?\DateTimeImmutable $linkingTokenExpiresAt = null,
        #[ORM\Column(type: 'datetime_immutable', nullable: true)]
        public ?\DateTimeImmutable $linkedAt = null,
    ) {}
}
