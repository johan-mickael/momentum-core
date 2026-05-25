# ADR-002 — Synchronous Domain Events with Async Email

## Context

Multiple aggregates must react when another aggregate changes state. Examples:
- When an order is paid → tickets should be confirmed
- When an order expires → tickets should be cancelled → slots released
- When an event is cancelled → all pending orders and tickets must be cancelled

Additionally, the confirmation email must not block payment processing.

## Decision

Use Symfony's EventDispatcher to dispatch domain events synchronously within the same DB transaction. All listeners (ticket confirmation, slot release, etc.) run in the same transaction.

Email specifically is sent via Symfony Mailer which routes `SendEmailMessage` to the AMQP async transport via Symfony Messenger. The mailer call returns immediately; the email is delivered by a worker process.

## Consequences

**Synchronous listeners**
- All state changes happen atomically — if a listener fails, the whole unit of work rolls back
- No eventual consistency to manage for inventory and ticket status
- Performance risk: if many listeners run and one is slow, the HTTP response is delayed

**Async email**
- Mailer failures (SMTP down, misconfigured DSN) never cause a payment to fail
- Email delivery can be retried via the Messenger failed transport
- There is no guarantee the buyer receives the email immediately after payment — this is acceptable

**Why not async for all events**
- Async listeners would introduce eventual consistency: the ticket might still show `RESERVED` for a brief period after the order is paid
- The inventory counter (`reservedQuantity`) would be stale temporarily, potentially allowing overselling
- The added complexity is not justified for the current load profile

## Trade-off Accepted

If the email worker is down, buyers will not receive confirmation emails until it is restarted. Tickets are still confirmed in the DB — the buyer can check their status via `GET /tickets/me`.
