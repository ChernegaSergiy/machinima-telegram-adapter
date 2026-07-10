<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaTelegramAdapter\Controller;

use App\Event\UserAuthenticatedEvent;
use Morfeditorial\MachinimaTelegramAdapter\Oidc\TelegramOidcProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class TelegramOidcController extends AbstractController
{
    public function __construct(
        private TelegramOidcProvider $provider,
        private EventDispatcherInterface $eventDispatcher,
        private RequestStack $requestStack,
    ) {
    }

    #[Route('/oauth/login/telegram', name: 'telegram_oidc_login')]
    public function login(): RedirectResponse
    {
        $state = bin2hex(random_bytes(32));
        $codeVerifier = bin2hex(random_bytes(32));
        $codeChallenge = rtrim(base64_url_encode(hash('sha256', $codeVerifier, true)), '=');

        $session = $this->requestStack->getSession();
        $session->set('telegram_oidc_state', $state);
        $session->set('telegram_oidc_code_verifier', $codeVerifier);

        return new RedirectResponse($this->provider->getAuthorizationUrl($state, $codeChallenge));
    }

    #[Route('/oauth/callback/telegram', name: 'telegram_oidc_callback')]
    public function callback(Request $request): Response
    {
        $code = $request->query->get('code');
        $state = $request->query->get('state');

        if (null === $code || null === $state) {
            $this->addFlash('error', 'Authorization failed.');

            return $this->redirectToRoute('app_login');
        }

        $session = $this->requestStack->getSession();
        $expectedState = $session->get('telegram_oidc_state');
        $codeVerifier = $session->get('telegram_oidc_code_verifier');

        $session->remove('telegram_oidc_state');
        $session->remove('telegram_oidc_code_verifier');

        if ($state !== $expectedState) {
            $this->addFlash('error', 'Invalid state parameter.');

            return $this->redirectToRoute('app_login');
        }

        try {
            $idToken = $this->provider->exchangeCodeForToken($code, $codeVerifier);
            $assertion = $this->provider->validateAssertion($idToken);

            $event = new UserAuthenticatedEvent($assertion);
            $this->eventDispatcher->dispatch($event);

            if ($event->getUser()) {
                return $this->redirectToRoute('app_index');
            }

            $this->addFlash('error', 'Authentication failed.');

            return $this->redirectToRoute('app_login');
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Authentication failed: ' . $e->getMessage());

            return $this->redirectToRoute('app_login');
        }
    }
}
