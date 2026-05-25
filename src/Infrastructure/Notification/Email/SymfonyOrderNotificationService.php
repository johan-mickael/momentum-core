<?php

declare(strict_types=1);

namespace App\Infrastructure\Notification\Email;

use App\Application\Order\Service\OrderNotificationServiceInterface;
use App\Domain\Event\EventRepositoryInterface;
use App\Domain\Event\ValueObject\EventId;
use App\Domain\Order\Order;
use App\Domain\User\UserRepositoryInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

final readonly class SymfonyOrderNotificationService implements OrderNotificationServiceInterface
{
    public function __construct(
        private UserRepositoryInterface $users,
        private EventRepositoryInterface $events,
        private MailerInterface $mailer,
    ) {}

    public function sendOrderConfirmation(Order $order): void
    {
        $user = $this->users->findById((string) $order->buyerId());

        if ($user === null) {
            return;
        }

        $event = $this->events->getById(EventId::fromString((string) $order->eventId()));

        $email = (new TemplatedEmail())
            ->to((string) $user->email())
            ->subject('Your order is confirmed — ' . $event->name())
            ->htmlTemplate('email/order_confirmation.html.twig')
            ->context([
                'eventName'   => $event->name(),
                'orderId'     => $order->id()->toString(),
                'ticketCount' => count($order->items()),
                'totalAmount' => $order->total()->amount() / 100,
                'currency'    => $order->total()->currency()->toString(),
                'paidAt'      => $order->paidAt(),
            ]);

        $this->mailer->send($email);
    }
}
