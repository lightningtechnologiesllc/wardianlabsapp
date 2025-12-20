<?php
declare(strict_types=1);

namespace Tests\Integration\App\Shared\Mailer;

use App\Shared\Mailer\ActiveCampaignTransportFactory;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mime\Email;
use Tests\Integration\App\Shared\Infrastructure\IntegrationTestCase;

final class ActiveCampaignMailerTest extends IntegrationTestCase
{
    public function testSend()
    {
        $this->markTestSkipped("Only for manual testing");
        $mailer = $this->getMailer();

        $email = (new Email())
            ->from('mongemalo@cleverconsulting.net')
            ->to('vicent@techabreath.com')
            ->subject('Time for Symfony Mailer!')
            ->text('Sending emails is fun again!')
            ->html('<p>See Twig integration for better HTML integration!</p>');

        $mailer->send($email);
    }

    private function getMailer(): Mailer
    {
    }
}
