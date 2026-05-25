# Order Lifecycle

## State Machine

```
                    ┌──────────────────────────────────────────────┐
                    │                                              │
              place()                                             │
                    ▼                                             │
          PENDING_PAYMENT ─── expire() or cancel() ──► CANCELLED  │
                    │                                             │
              markAsPaid()                                        │
                    │                                             │
                    ▼                                             │
                  PAID ──────────── refund() ─────────────────────┘
```

## Transitions

| Method | From | To | Guard | Throws |
|---|---|---|---|---|
| `place()` | _(new)_ | PENDING_PAYMENT | items not empty | `EmptyOrderItems` |
| `markAsPaid()` | PENDING_PAYMENT | PAID | status is not PAID and not CANCELLED | `OrderAlreadyPaid`, `CannotPayCancelledOrder` |
| `expire()` | PENDING_PAYMENT | CANCELLED | status is PENDING_PAYMENT | _(returns silently if already cancelled)_ |
| `cancel()` | PENDING_PAYMENT | CANCELLED | status is PENDING_PAYMENT | _(returns silently if not pending)_ |
| `refund()` | PAID | CANCELLED | status is PAID | `CannotRefundOrder::notPaid()` |

`CANCELLED` is a terminal state reachable via three different paths (expiry, cancellation, refund). The domain events distinguish which path was taken.

## TTL

Orders expire 15 minutes after placement (`Order::TTL_MINUTE = 15`). This is enforced by the `ExpireOrdersTask` scheduler, not the domain itself — the domain's `expire()` method accepts any `$now` value and does not validate against the TTL.

## PaymentReference

The `PaymentReference` value object stores the Stripe `payment_intent` ID. It is:
- `null` for unpaid orders
- Set when `markAsPaid(paidAt, ?PaymentReference)` is called
- Required by `refund()` to identify the charge in Stripe's system

An order where the `PaymentReference` is `null` cannot be refunded via the API. This can occur if the order was paid through a non-Stripe path or before this field was introduced.

## Domain Events Per State

| State Reached | Domain Event |
|---|---|
| PENDING_PAYMENT | `OrderPlaced(orderId, buyerId, eventId, ticketIds[])` |
| PAID | `OrderPaid(orderId, ticketIds[])` |
| CANCELLED via expire | `OrderExpired(orderId, ticketIds[])` |
| CANCELLED via cancel | `OrderCancelled(orderId, ticketIds[])` |
| CANCELLED via refund | `OrderRefunded(orderId, ticketIds[], paymentReference)` |

## Total Calculation

`Order::computeTotal()` sums `OrderItem::unitPrice()` for all items. The total is computed at placement time and stored — subsequent price changes on the category do not retroactively change the order total.

## Inventory Impact

| Event | Effect on Inventory |
|---|---|
| Order placed | Slots reserved (`reservedQuantity++`) |
| Order paid | No change — slots remain reserved |
| Order expired / cancelled / refunded | Slots released (`reservedQuantity--`) via `TicketCancelled → ReleaseTicketSlotOnTicketCancelled` |
