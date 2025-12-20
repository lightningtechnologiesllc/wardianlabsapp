<?php
declare(strict_types=1);

namespace App\Shared\Mailer;

use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;

final class ActiveCampaignTransportFactory extends AbstractTransportFactory
{
    public function create(Dsn $dsn): TransportInterface
    {
        $clientId = $dsn->getUser();
        $apiKey = $dsn->getPassword();

        return new ActiveCampaignTransport($clientId, $apiKey, $this->dispatcher, $this->logger);
    }

    public function createFromString(string $dsn): TransportInterface
    {
        return $this->create(Dsn::fromString($dsn));
    }

    /** @return string[] */
    protected function getSupportedSchemes(): array
    {
        return ['activecampaign+api'];
    }
}
