<?php

declare(strict_types=1);

namespace UI\Http\Controller\Auth;

use Application\Auth\Command\LoginWithGoogleCommand;
use Application\Auth\CommandHandler\LoginWithGoogleUseCase;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GoogleUser;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Auth')]
final class GoogleAuthController extends AbstractController
{
    public function __construct(
        private readonly LoginWithGoogleUseCase $useCase,
        private readonly ClientRegistry $clientRegistry,
        private readonly int $jwtTokenTtl,
        private readonly string $frontendUrl,
    ) {
    }

    #[Route('/auth/google', name: 'app_google_redirect', methods: ['GET'])]
    #[OA\Get(
        path: '/api/auth/google',
        operationId: 'googleRedirect',
        summary: 'Redirect to Google consent screen',
        security: [],
        responses: [new OA\Response(response: 302, description: 'Redirect to Google')],
    )]
    public function redirectToGoogle(): RedirectResponse
    {
        return $this->clientRegistry
            ->getClient('google')
            ->redirect(['email', 'profile'], []);
    }

    #[Route('/auth/google/callback', name: 'app_google_callback', methods: ['GET'])]
    #[OA\Get(
        path: '/api/auth/google/callback',
        operationId: 'googleCallback',
        summary: 'Google OAuth callback — sets jwt_token cookie and redirects to frontend',
        security: [],
        responses: [
            new OA\Response(response: 302, description: 'Redirect to frontend with jwt_token cookie set'),
        ],
    )]
    public function callback(Request $request): RedirectResponse
    {
        try {
            $client = $this->clientRegistry->getClient('google');
            $googleUser = $client->fetchUser();

            if (!$googleUser instanceof GoogleUser) {
                return new RedirectResponse($this->frontendUrl.'/login?error=google_failed');
            }

            $token = ($this->useCase)(new LoginWithGoogleCommand(
                email: $googleUser->getEmail() ?? '',
                firstName: $googleUser->getFirstName() ?? '',
                lastName: $googleUser->getLastName() ?? '',
            ));
        } catch (IdentityProviderException) {
            return new RedirectResponse($this->frontendUrl.'/login?error=google_failed');
        }

        $response = new RedirectResponse($this->frontendUrl);
        $response->headers->setCookie(
            Cookie::create('jwt_token')
                ->withValue($token)
                ->withExpires(time() + $this->jwtTokenTtl)
                ->withPath('/')
                ->withHttpOnly()
                ->withSecure($request->isSecure())
                ->withSameSite(Cookie::SAMESITE_LAX)
        );

        return $response;
    }
}
