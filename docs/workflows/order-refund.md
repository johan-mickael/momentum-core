# Workflow: Order Refund

## Trigger

A buyer or staff member requests a full refund for a paid order.

## Actors

- **Buyer** — the person who placed and paid for the order
- **Staff / Admin** — can initiate refunds on behalf of buyers

## Preconditions

- The order is in `PAID` status
- The order has a `PaymentReference` (i.e., it was paid via Stripe Checkout — not via a manual override)
- The actor is either the order's buyer or a staff member

## Why `PaymentReference` Is Required

The `PaymentReference` stores the Stripe `payment_intent` ID, which is the handle Stripe needs to issue a refund. It is stored on the order when `MarkOrderAsPaidCommand` is processed from the Stripe webhook:

```
checkout.session.completed
  └─ session.payment_intent → PaymentReference stored on Order
```

Orders paid before this field was introduced, or orders marked paid through a non-Stripe path, will have `paymentReference = null`. Refund is blocked with `OrderHasNoPaymentReference`.

## Main Flow

### Step 1 — Request refund

```
POST /orders/{orderId}/refund
Authorization: Bearer <buyer-or-staff-jwt>
→ 204 No Content
```

No request body required.

### Step 2 — Authorization

`RefundOrderCommandHandler`:
1. Fetch order from `OrderRepositoryInterface`
2. `AuthorizationService::authorize(actor, Action::CANCEL, OrderContext(order))`
   - Returns true for: actor is the buyer OR actor is staff

### Step 3 — Validate refundable state

3. Assert `order.paymentReference() !== null` — throws `OrderHasNoPaymentReference`
4. `order.refund()` domain method is called next (step 4), which asserts `status === PAID` — throws `CannotRefundOrder::notPaid()` if not paid

### Step 4 — Issue Stripe refund

```php
$this->refundService->refund($paymentReference);
```

`StripeRefundService` calls:
```php
$this->stripe->refunds->create([
    'payment_intent' => $paymentReference->toString(),
]);
```

If Stripe returns an error (e.g., charge already refunded), an exception propagates and the order is **not** marked as refunded in our database. This preserves consistency — we only record a refund after Stripe confirms it.

### Step 5 — Mark order as refunded

```php
$order->refund($this->clock->now());
```

- Order status: `PAID → CANCELLED`
- `cancelledAt` set to current timestamp
- `OrderRefunded(orderId, ticketIds[], paymentReference)` domain event recorded

### Step 6 — Persist and dispatch

Order saved, events dispatched, transaction flushed.

### Step 7 — Tickets cancelled (listener)

`CancelTicketsOnOrderRefunded` fires on `OrderRefunded`:

```
For each ticketId:
  ticket.cancel() → CANCELLED
  TicketCancelled dispatched
    └─► ReleaseTicketSlotOnTicketCancelled
          └─ event.releaseTicketInCategory() → slots freed
```

## Failure Cases

| Scenario | Thrown | HTTP |
|---|---|---|
| Order not found | `OrderNotFound` | 404 |
| Actor is neither buyer nor staff | `CancelOrderForbidden` | 403 |
| Order not `PAID` | `CannotRefundOrder::notPaid()` | 422 |
| No payment reference | `OrderHasNoPaymentReference` | 422 |
| Stripe refund rejected | Stripe exception propagates | 500 |

## Postconditions

- Order status: `CANCELLED`
- All tickets status: `CANCELLED`
- Stripe refund issued (full amount, same currency)
- Ticket category slots released back to inventory

## Business Rules

- Refunds are always full — no partial refunds in the current implementation
- A cancelled order cannot be refunded (only paid orders can)
- Refunding releases ticket slots, which may allow other buyers to purchase
- The Stripe `payment_intent` ID is stored as `PaymentReference` and used as the refund handle
- Stripe is called first; DB is only updated if Stripe succeeds (avoids marking an order refunded when Stripe didn't actually refund)

## Dual-Write Risk

If Stripe refund succeeds but the DB transaction fails, the money is returned to the buyer but our DB still shows the order as `PAID`. This is recoverable: query the Stripe dashboard for the refund record and manually update the order via admin tooling. This risk is accepted for MVP — a two-phase approach would require a "pending refund" status.
