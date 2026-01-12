<?php
declare(strict_types=1);

namespace App\Admin\Ui\Adapter\Http\Stripe;

use App\Admin\Domain\Stripe\PendingStripeInstallation;
use App\Admin\Domain\Stripe\PendingStripeInstallationRepository;
use App\Shared\Domain\Stripe\StripeAccessToken;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

#[Route('/admin/stripe/submit-email', name: 'admin_stripe_submit_email', methods: ['POST'])]
final readonly class SubmitEmailController
{
    public function __construct(
        private LoggerInterface                     $logger,
        private RouterInterface                     $router,
        private PendingStripeInstallationRepository $pendingInstallationRepository,
        private MailerInterface                     $mailer,
        private Environment                         $twig,
        private string                              $defaultMailerFrom,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $email = $request->request->get('email');
        $session = $request->getSession();
        $accessTokenData = $session->get('pending_stripe_access_token');

        if ($accessTokenData === null) {
            $this->logger->warning('No pending Stripe access token found in session');
            return new Response(
                $this->twig->render('admin/stripe/installation_error.html.twig', [
                    'message' => 'Session expired. Please try installing the Stripe app again.',
                ]),
                Response::HTTP_BAD_REQUEST
            );
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new Response(
                $this->twig->render('admin/stripe/installation_ask_email.html.twig', [
                    'error' => 'Please provide a valid email address.',
                ]),
                Response::HTTP_BAD_REQUEST
            );
        }

        $stripeAccessToken = StripeAccessToken::fromArray($accessTokenData);
        $pendingInstallation = PendingStripeInstallation::create($stripeAccessToken, $email);
        $this->pendingInstallationRepository->save($pendingInstallation);

        $this->sendInstallationEmail($pendingInstallation);

        $this->logger->info('Created pending Stripe installation with user-provided email', [
            'email' => $email,
            'stripe_user_id' => $stripeAccessToken->stripeUserId,
            'linking_token' => $pendingInstallation->getLinkingToken(),
        ]);

        // Clear session data
        $session->remove('pending_stripe_access_token');

        return new Response(
            $this->twig->render('admin/stripe/installation_email_sent.html.twig', [
                'email' => $email,
            ]),
            Response::HTTP_OK
        );
    }

    private function sendInstallationEmail(PendingStripeInstallation $pendingInstallation): void
    {
        $completeUrl = $this->router->generate(
            'admin_stripe_complete_installation',
            ['token' => $pendingInstallation->getLinkingToken()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $emailContent = $this->twig->render('admin/emails/stripe_installation_pending.html.twig', [
            'complete_url' => $completeUrl,
            'expires_at' => $pendingInstallation->getExpiresAt(),
        ]);

        $email = (new Email())
            ->from($this->defaultMailerFrom)
            ->to($pendingInstallation->getEmail())
            ->subject('Complete Your Stripe Installation')
            ->html($emailContent);

        try {
            $this->mailer->send($email);
            $this->logger->info('Sent Stripe installation email', [
                'email' => $pendingInstallation->getEmail(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send Stripe installation email', [
                'email' => $pendingInstallation->getEmail(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
