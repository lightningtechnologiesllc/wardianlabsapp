<?php
declare(strict_types=1);

namespace App\Shared\Mailer;

use CS_REST_Transactional_ClassicEmail;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use SensitiveParameter;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractApiTransport;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\HttpClient\ResponseInterface;
use function sprintf;

final class ActiveCampaignTransport extends AbstractApiTransport
{
    private CS_REST_Transactional_ClassicEmail $activeCampaignClient;

    public function __construct(
        private readonly string                       $clientId,
        #[SensitiveParameter] private readonly string $apiKey,
        ?EventDispatcherInterface                     $dispatcher = null,
        ?LoggerInterface                              $logger = null,
    )
    {
        parent::__construct(null, $dispatcher, $logger);

        $auth = array("api_key" => $this->apiKey);
        $this->activeCampaignClient = new CS_REST_Transactional_ClassicEmail($auth, $clientId);
    }

    public function __toString(): string
    {
        return sprintf('activecampaign+api://%s:%s', $this->clientId, $this->apiKey);
    }

    protected function doSendApi(SentMessage $sentMessage, Email $email, Envelope $envelope): ResponseInterface
    {
        $message = $this->createMessage($email, $envelope);

        $consent_to_track = 'yes'; # Valid: 'yes', 'no', 'unchanged'

        $result = $this->activeCampaignClient->send($message, null, $consent_to_track);

        $this->getLogger()->debug('ActiveCampaignTransport send result', [
            'response' => $result->response,
            'was_successful' => $result->was_successful(),
        ]);

        if (!$result->was_successful()) {
            $this->getLogger()->error('Error sending email using ActiveCampaignTransport', ['response' => $result->response]);
            throw new RuntimeException('Error sending email using ActiveCampaignTransport: ' . $result->response->message);
        }

        return new MockResponse();
    }

    /** @return array<string, list<string>|resource|string|null> */
    private function createMessage(Email $email, Envelope $envelope): array
    {
        $recipients = [];

        foreach ($this->getRecipients($email, $envelope) as $recipient) {
            $recipients[] = $recipient->getAddress();
        }

        return array(
            "From" => $envelope->getSender()->getAddress(),
            "Subject" => $email->getSubject(),
            "To" => $recipients,
            "HTML" => $email->getHtmlBody(),
        );
    }
}
