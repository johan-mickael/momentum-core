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
    private const string REDIRECT_COOKIE = 'oauth_redirect';

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
        parameters: [
            new OA\Parameter(name: 'redirect', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Frontend path to return to after login'),
        ],
        responses: [new OA\Response(response: 302, description: 'Redirect to Google')],
    )]
    public function redirectToGoogle(Request $request): RedirectResponse
    {
        $response = $this->clientRegistry
            ->getClient('google')
            ->redirect(['email', 'profile'], []);

        $redirectPath = $this->sanitizeRedirectPath($request->query->getString('redirect'));
        if (null !== $redirectPath) {
            $response->headers->setCookie(
                Cookie::create(self::REDIRECT_COOKIE)
                    ->withValue($redirectPath)
                    ->withExpires(time() + 300)
                    ->withPath('/')
                    ->withHttpOnly()
                    ->withSecure($request->isSecure())
                    ->withSameSite(Cookie::SAMESITE_LAX)
            );
        }

        return $response;
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

        $redirectPath = $this->sanitizeRedirectPath($request->cookies->get(self::REDIRECT_COOKIE));

        $response = new RedirectResponse($this->frontendUrl.($redirectPath ?? ''));
        $response->headers->setCookie(
            Cookie::create('jwt_token')
                ->withValue($token)
                ->withExpires(time() + $this->jwtTokenTtl)
                ->withPath('/')
                ->withHttpOnly()
                ->withSecure($request->isSecure())
                ->withSameSite(Cookie::SAMESITE_LAX)
        );
        $response->headers->clearCookie(self::REDIRECT_COOKIE, '/');

        return $response;
    }

    private function sanitizeRedirectPath(?string $path): ?string
    {
        if (null === $path || '' === $path) {
            return null;
        }

        if (!str_starts_with($path, '/') || str_starts_with($path, '//') || str_contains($path, '\\')) {
            return null;
        }

        return $path;
    }
}
