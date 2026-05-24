<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Webhook;

use App\Application\Order\Command\MarkOrderAsPaidCommand;
use App\Application\Order\CommandHandler\MarkOrderAsPaidUseCase;
use App\Application\Order\Service\WebhookParserInterface;
use DateTimeInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/stripe/webhook', methods: ['POST'])]
final class StripeWebhookController extends AbstractController
{
    public function __construct(
        private readonly WebhookParserInterface $webhookParser,
        private readonly MarkOrderAsPaidUseCase $markOrderAsPaid,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $event = $this->webhookParser->parse(
            $request->getContent(),
            $request->headers->get('Stripe-Signature', ''),
        );

        if ($event->type === 'checkout.session.completed' && $event->orderId !== null) {
            ($this->markOrderAsPaid)(
                new MarkOrderAsPaidCommand(
                    orderId: $event->orderId,
                    paidAt:  $event->occurredAt->format(DateTimeInterface::ATOM),
                ),
            );
        }

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
