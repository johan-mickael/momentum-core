# ADR-004 — Stripe Refund Before DB Update

## Context

When refunding an order, two external state changes must happen:
1. Stripe must issue the refund
2. Our DB must mark the order as `CANCELLED`

Both can fail. The order matters.

## Decision

Call Stripe first. Only update the DB if Stripe succeeds.

```php
$this->refundService->refund($paymentReference);  // Stripe call
$order->refund($this->clock->now());               // DB mutation
$this->orders->save($order);
```

## Rationale

The worst-case scenarios:

| Stripe | DB | State | Recoverability |
|---|---|---|---|
| Fails | Not reached | Order still PAID, no refund issued | Clean — user retries |
| Succeeds | Fails | Money refunded, DB shows PAID | Inconsistent — must fix manually |

Both are bad. But the second is worse — the buyer has received money back but our system still shows a paid order, potentially allowing re-use of a ticket that should be cancelled.

The first case is safe: the user retries the refund request, Stripe detects the already-refunded charge and returns an error. Our support team investigates.

## Trade-off Accepted

There is a narrow window where Stripe succeeds and our DB update fails. This is an accepted risk for MVP. A two-phase approach (mark order as "refund_pending" first, then call Stripe, then mark "cancelled") would eliminate it at the cost of a new status and more complex state management.

A Stripe webhook for `charge.refunded` or `refund.created` could be used to reconcile inconsistencies, but that is not implemented.
