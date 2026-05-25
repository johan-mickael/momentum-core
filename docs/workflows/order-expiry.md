# Workflow: Automatic Order Expiry

## Purpose

Prevent inventory lock-out by releasing ticket slots when a buyer abandons the checkout flow.

## Business Goal

When a buyer places an order but never completes payment, the reserved slots are unavailable to other buyers. Without automatic expiry, abandoned orders would permanently reduce available inventory.

## Mechanism

Every order has an `expiresAt` timestamp set to `placedAt + 15 minutes` (the `Order::TTL_MINUTE` constant). The `ExpireOrdersTask` scheduler task runs every 60 seconds and cancels all pending orders past their expiry time.

## Actors

- **System** — the Symfony Scheduler worker

## Flow

### Step 1 — Periodic task fires

`ExpireOrdersTask` is annotated with:

```php
#[AsPeriodicTask(frequency: '60 seconds', jitter: 5)]
```

The Symfony Scheduler worker invokes `ExpireOrdersTask::__invoke()` every ~60 seconds (±5 second jitter to avoid thundering herd).

### Step 2 — Load expired orders

`ExpireOrdersCommandHandler`:
```php
$expired = $this->orders->findPendingExpiredBefore($now);
```

`DoctrineOrderRepository::findPendingExpiredBefore()` queries:
```sql
SELECT o FROM "order" o
WHERE o.status = 'PENDING_PAYMENT'
AND o.expiresAt <= :now
```

### Step 3 — Expire each order

For each expired order:
```php
$order->expire($now);         // PENDING_PAYMENT → CANCELLED, records OrderExpired
$this->orders->save($order);
$this->dispatcher->dispatch(...$order->pullRecordedEvents());
```

Single `flush()` after all orders are processed.

### Step 4 — Cascade: tickets cancelled

`CancelTicketsOnOrderExpired` fires on `OrderExpired`:
```
For each ticketId:
  ticket.cancel() → CANCELLED
  TicketCancelled dispatched
    └─► ReleaseTicketSlotOnTicketCancelled
          └─ event.releaseTicketInCategory() → slot freed
```

## Edge Cases

**Payment arrives after expiry**
If Stripe delivers `checkout.session.completed` after the order has been expired and cancelled, the webhook handler calls `MarkOrderAsPaidCommand`. The `MarkOrderAsPaidCommandHandler` calls `order.markAsPaid()`, which throws `CannotPayCancelledOrder`. This exception is caught and silently ignored in `StripePaymentWebhookHandler`. Stripe receives a 200 response and stops retrying. The buyer has paid but the order is cancelled — a refund must be issued manually or via Stripe's refund flow.

**Order expires while Stripe checkout is open**
This is expected. The buyer may still complete the Stripe payment UI, triggering the webhook. The webhook is then handled as above.

**Scheduler worker is down**
If the worker is not running, orders accumulate past their TTL without being cancelled. Slots remain locked. The Symfony Scheduler's `stateful()` and `processOnlyLastMissedRun(true)` configuration ensures that when the worker restarts, only the most recent missed run is executed rather than all missed runs stacking up.

## Configuration

The scheduler state is cached to allow missed run detection:

```php
// src/Schedule.php
(new SymfonySchedule())
    ->stateful($this->cache)
    ->processOnlyLastMissedRun(true)
```

The Scheduler worker is started with:
```bash
bin/console messenger:consume scheduler_default
```
