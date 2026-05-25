<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Webhook;

use App\Application\Order\Service\PaymentWebhookHandlerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/stripe/webhook', methods: ['POST'])]
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
