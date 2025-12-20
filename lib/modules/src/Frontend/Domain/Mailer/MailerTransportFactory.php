<?php
declare(strict_types=1);

namespace App\Frontend\Domain\Mailer;

use App\Shared\Mailer\ActiveCampaignTransportFactory;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportInterface;

final class MailerTransportFactory
{
    public function __construct(
        private readonly ActiveCampaignTransportFactory $activeCampaignTransportFactory
    )
    {
    }

    public function fromDsn(string $dsn): TransportInterface
    {
        $defaultFactories = iterator_to_array(Transport::getDefaultFactories());
        $factories = array_merge($defaultFactories, [$this->activeCampaignTransportFactory]);
        $transportFactory = new Transport($factories);

        return $transportFactory->fromString($dsn);
    }
}
