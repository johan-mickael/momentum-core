<?php

declare(strict_types=1);

namespace UI\Http\Controller\Cart;

use Application\Cart\Command\ClearCartCommand;
use Application\Cart\CommandHandler\ClearCartUseCase;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use UI\Http\Controller\HttpController;

#[Route('/events/{eventId}/cart', methods: ['DELETE'])]
#[OA\Delete(
    path: '/api/events/{eventId}/cart',
    operationId: 'clearCart',
    summary: 'Abandon the cart entirely, releasing every held ticket',
    tags: ['Cart'],
    parameters: [
        new OA\Parameter(name: 'eventId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 204, description: 'Cart cleared (idempotent — also returned if no cart existed)'),
        new OA\Response(response: 401, description: 'Not authenticated', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 403, description: 'Cart belongs to another buyer', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
    ],
)]
final class ClearCartController extends HttpController
{
    public function __construct(
        private readonly ClearCartUseCase $useCase,
    ) {
    }

    public function __invoke(string $eventId): Response
    {
        ($this->useCase)(
            new ClearCartCommand(eventId: $eventId),
            $this->getUserContext(),
        );

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
