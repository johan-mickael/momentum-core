<?php

declare(strict_types=1);

namespace UI\Http\Controller\Auth;

use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/auth/logout', methods: ['POST'])]
#[OA\Post(
    path: '/api/auth/logout',
    operationId: 'logout',
    summary: 'Clear jwt_token cookie',
    tags: ['Auth'],
    security: [],
    responses: [
        new OA\Response(response: 204, description: 'Logged out — jwt_token cookie cleared'),
    ],
)]
final class LogoutController extends AbstractController
{
    public function __invoke(): Response
    {
        $response = new Response(null, Response::HTTP_NO_CONTENT);
        $response->headers->clearCookie('jwt_token', '/', null, true, true);

        return $response;
    }
}
