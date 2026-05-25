# Event-Driven Architecture

## How Domain Events Work

Aggregates record events internally using the `RecordsDomainEvents` trait. Application handlers pull these events after persisting and dispatch them via `DomainEventDispatcherInterface`, which wraps Symfony's EventDispatcher.

```
Aggregate::mutate()
  └─► recordEvent(new SomethingHappened(...))

Handler::__invoke()
  ├─ aggregate.mutate()
  ├─ repository.save(aggregate)
  ├─ dispatcher.dispatch(...aggregate.pullRecordedEvents())
  └─ transactionManager.flush()
```

Events are dispatched **after** the save call but **before** the DB flush. Listeners run synchronously within the same transaction, so if a listener fails, the whole unit of work rolls back.

## Event Map

### User Events

| Event | Raised By | Listeners |
|---|---|---|
| `UserRegistered` | `User::register()` | _(none currently)_ |

### Event Aggregate Events

| Event | Raised By | Listeners |
|---|---|---|
| `EventPublished` | `Event::publish()` | _(none currently)_ |
| `EventCancelled` | `Event::cancel()` | `CancelTicketsOnEventCancelled`, `CancelOrdersOnEventCancelled` |

### Order Events

| Event | Raised By | Listeners |
|---|---|---|
| `OrderPlaced` | `Order::place()` | _(none currently)_ |
| `OrderPaid` | `Order::markAsPaid()` | `ConfirmTicketsOnOrderPaid`, `SendConfirmationEmailOnOrderPaid` |
| `OrderCancelled` | `Order::cancel()` | `CancelTicketsOnOrderCancelled` |
| `OrderExpired` | `Order::expire()` | `CancelTicketsOnOrderExpired` |
| `OrderRefunded` | `Order::refund()` | `CancelTicketsOnOrderRefunded` |

### Ticket Events

| Event | Raised By | Listeners |
|---|---|---|
| `TicketReserved` | `Ticket::reserve()` | _(none currently)_ |
| `TicketConfirmed` | `Ticket::confirm()` | _(none currently)_ |
| `TicketCancelled` | `Ticket::cancel()` | `ReleaseTicketSlotOnTicketCancelled` |
| `TicketUsed` | `Ticket::markAsUsed()` | _(none currently)_ |

## Cross-Aggregate Cascade

When an event is cancelled, the full cascade is:

```
EventCancelled
  ├─► CancelOrdersOnEventCancelled
  │     └─ order.cancel() ──► OrderCancelled
  │           └─► CancelTicketsOnOrderCancelled
  │                 └─ ticket.cancel() ──► TicketCancelled
  │                       └─► ReleaseTicketSlotOnTicketCancelled
  │                             └─ event.releaseTicketInCategory()
  │
  └─► CancelTicketsOnEventCancelled
        └─ ticket.cancel() ──► TicketCancelled
              └─► ReleaseTicketSlotOnTicketCancelled
```

When an order is paid:

```
OrderPaid
  ├─► ConfirmTicketsOnOrderPaid
  │     └─ ticket.confirm() ──► TicketConfirmed
  │
  └─► SendConfirmationEmailOnOrderPaid
        └─ mailer.send() → Messenger (async) → AMQP worker
```

When an order expires or is refunded:

```
OrderExpired / OrderRefunded / OrderCancelled
  └─► CancelTickets(OnOrderExpired|OnOrderCancelled|OnOrderRefunded)
        └─ ticket.cancel() ──► TicketCancelled
              └─► ReleaseTicketSlotOnTicketCancelled
                    └─ event.releaseTicketInCategory()
```

## Async Email

Email is the only operation sent to the async Messenger transport. All other listeners run synchronously.

The Symfony Mailer sends a `SendEmailMessage` to the Messenger bus when `MailerInterface::send()` is called. The `messenger.yaml` routing config sends `SendEmailMessage` to the `async` transport (AMQP/RabbitMQ). A Messenger worker processes the queue.

**Why async**: A mailer failure (SMTP down, misconfigured DSN) must not roll back a payment. The confirmation email is a side effect — the payment is the critical path.

**Risk**: If the worker fails to process the email, it lands in the `failed` transport (Doctrine dead-letter queue) for manual replay via `bin/console messenger:failed:retry`.

## Listener Registration

Listeners are registered in `config/services.yaml` using Symfony's `kernel.event_listener` tag with the domain event class as the event name:

```yaml
App\Application\Order\EventListener\ConfirmTicketsOnOrderPaid:
    tags:
        - { name: kernel.event_listener, event: App\Domain\Order\Event\OrderPaid }
```

## Adding New Listeners

1. Create the listener class in `Application/{Aggregate}/EventListener/`
2. Inject repositories and services via constructor
3. Implement `__invoke(SomeDomainEvent $event): void`
4. Register in `config/services.yaml` with the event tag
5. No changes to the aggregate or the handler that raises the event
