<?php
declare(strict_types=1);

namespace App\Admin\Ui\Adapter\Http;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

#[Route('/admin/login', name: 'admin_login', methods: ['GET'])]
final class LoginController
{
    public function __construct(
        private readonly Security         $security,
        private readonly Environment      $twig,
    )
    {
    }

    public function __invoke(): Response
    {
        $user = $this->security->getUser();

        return new Response($this->twig->render('admin/login.html.twig', [
            'user' => $user,
        ]));
    }
}
