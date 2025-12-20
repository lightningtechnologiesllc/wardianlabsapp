<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony;

use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Twig\Environment;

abstract readonly class WebController
{
    public function __construct(
        private readonly Environment           $twig,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly RequestStack          $requestStack
    ) {
    }

    public function render(string $templatePath, array $arguments = []): SymfonyResponse
    {
        return new SymfonyResponse($this->twig->render($templatePath, $arguments));
    }

    public function redirect(string $routeName, array $params = []): RedirectResponse
    {
        return new RedirectResponse($this->urlGenerator->generate($routeName, $params), 302);
    }

    public function redirectWithMessage(string $routeName, array $routeParams, string $message): RedirectResponse
    {
        $this->addFlashFor('message', [$message]);

        return $this->redirect($routeName, $routeParams);
    }

    public function redirectWithErrors(
        string $routeName,
        array $routeParams,
        ConstraintViolationListInterface $errors,
        Request $request
    ): RedirectResponse {
        $this->addFlashFor('errors', $this->formatFlashErrors($errors));
        $this->addFlashFor('inputs', $request->request->all());

        return new RedirectResponse($this->urlGenerator->generate($routeName, $routeParams), 302);
    }

    protected function persistValueInSession(Request $request, $sessionKey, string $requestKey, mixed $defaultValue = null): void
    {
        $session = $request->getSession();
        $session->set($sessionKey, $request->get($requestKey, $defaultValue));
    }

    protected function addFlashFor(string $prefix, array $messages): void
    {
        foreach ($messages as $key => $message) {
            try {
                $this->requestStack->getSession()->getFlashBag()->set($prefix.'.'.$key, $message);
            } catch (SessionNotFoundException $e) {
            }
        }
    }

    private function formatFlashErrors(ConstraintViolationListInterface $violations): array
    {
        $errors = [];
        foreach ($violations as $violation) {
            $errors[str_replace(['[', ']'], ['', ''], $violation->getPropertyPath())] = $violation->getMessage();
        }

        return $errors;
    }
}
