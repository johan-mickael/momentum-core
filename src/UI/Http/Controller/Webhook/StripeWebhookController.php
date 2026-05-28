<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Webhook;

use App\Application\Order\Service\PaymentWebhookHandlerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/stripe/webhook', methods: ['POST'])]
#[OA\Post(
    path: '/api/stripe/webhook',
    operationId: 'stripeWebhook',
    summary: 'Receive Stripe payment events',
    description: 'Called by Stripe after payment events. Signature validated via Stripe-Signature HMAC header.',
    tags: ['Webhooks'],
    security: [],
    parameters: [
        new OA\Parameter(name: 'Stripe-Signature', in: 'header', required: true, description: 'HMAC signature from Stripe', schema: new OA\Schema(type: 'string')),
    ],
    requestBody: new OA\RequestBody(required: true, content: new OA\MediaType(mediaType: 'application/json', schema: new OA\Schema(type: 'object', description: 'Raw Stripe event payload'))),
    responses: [
        new OA\Response(response: 204, description: 'Event processed'),
        new OA\Response(response: 400, description: 'Invalid Stripe signature', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
    ],
)]
final class StripeWebhookController extends AbstractController
{
    public function __construct(
        private readonly PaymentWebhookHandlerInterface $webhookHandler,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $this->webhookHandler->handle(
            $request->getContent(),
            $request->headers->all(),
        );

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
