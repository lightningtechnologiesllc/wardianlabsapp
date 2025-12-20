<?php
declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine\Stripe;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'stripe_accounts')]
class DoctrineStripeAccount
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id;

    public function __construct(
        #[ORM\Column(unique: true)]
        public string $accountId,
        #[ORM\Column()]
        public string $tenantId,
        #[ORM\Column(unique: true)]
        public string $stripeProviderAccountId,
        #[ORM\Column()]
        public string $displayName,
        #[ORM\Column()]
        public string $stripeUserId,
        #[ORM\Column()]
        public string $accessToken,
        #[ORM\Column()]
        public string $refreshToken,
        #[ORM\Column()]
        public string $publishableKey,
        #[ORM\Column()]
        public string $scope,
        #[ORM\Column(type: Types::BOOLEAN)]
        public bool $livemode,
        #[ORM\Column()]
        public string $tokenType,
        #[ORM\Column(nullable: true)]
        public ?string $webhookSecret = null,
        #[ORM\Column(nullable: true)]
        public ?string $webhookEndpointId = null,
    )
    {
    }
}
