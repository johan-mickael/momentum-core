<?php

declare(strict_types=1);

namespace Infrastructure\Symfony\Messaging;

use Application\Common\Bus\CommandBusInterface;
use Application\Common\Bus\Exception\CannotDispatchBusMessage;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class MessengerCommandBus implements CommandBusInterface
{
    public function __construct(
        private MessageBusInterface $bus,
    ) {
    }

    /** @param array<mixed> $stamps */
    public function dispatch(object $command, array $stamps = []): Envelope
    {
        try {
            return $this->bus->dispatch(new Envelope($command, $stamps));
        } catch (ExceptionInterface $exception) {
            throw new CannotDispatchBusMessage($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
