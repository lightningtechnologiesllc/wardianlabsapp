<?php
declare(strict_types=1);

namespace App\Admin\Ui\Adapter\Http\TenantConfig;

use App\Admin\Application\Tenant\UpdateTenantConfigUseCase;
use App\Admin\Domain\User\User;
use App\Admin\Ui\Form\TenantConfigType;
use App\Shared\Infrastructure\Symfony\WebController;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

#[Route('/admin/tenant/config', name: 'admin_tenant_config', methods: ['GET', 'POST'])]
final readonly class TenantConfigController extends WebController
{
    public function __construct(
        private Security                  $security,
        private Environment               $twig,
        private UrlGeneratorInterface     $urlGenerator,
        private RequestStack              $requestStack,
        private FormFactoryInterface      $formFactory,
        private LoggerInterface           $logger,
        private UpdateTenantConfigUseCase $updateTenantConfigUseCase,
    )
    {
        parent::__construct($twig, $urlGenerator, $requestStack);
    }

    public function __invoke(Request $request): Response
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $tenant = $user->getTenants()->first();

        // Create form with tenant data
        $hasCustomEmail = !empty($tenant->getEmailDSN()) || !empty($tenant->getEmailFromAddress());
        $form = $this->formFactory->create(TenantConfigType::class, [
            'name' => $tenant->getName(),
            'subdomain' => $tenant->getSubdomain(),
            'useCustomEmail' => $hasCustomEmail,
            'emailDSN' => $tenant->getEmailDSN(),
            'emailFromAddress' => $tenant->getEmailFromAddress(),
        ]);

        // Handle form submission
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // If custom email is not enabled, clear the email settings
            $emailDSN = $data['useCustomEmail'] ? $data['emailDSN'] : '';
            $emailFromAddress = $data['useCustomEmail'] ? $data['emailFromAddress'] : '';

            // Update tenant using the use case
            ($this->updateTenantConfigUseCase)(
                $tenant,
                $data['name'],
                $data['subdomain'],
                $emailDSN,
                $emailFromAddress
            );
            $this->addFlashFor('success', ['Tenant configuration updated successfully']);

            $this->logger->info("Tenant configuration updated");

            return $this->redirectWithMessage('admin_tenant_config', [], 'Tenant configuration updated successfully');
        }

        // Render form (on GET or when form has errors)
        return new Response($this->twig->render('admin/tenant/config.html.twig', [
            'user' => $user,
            'tenant' => $tenant,
            'form' => $form->createView(),
            'current_menu_section' => 'tenant_config'
        ]));
    }
}
