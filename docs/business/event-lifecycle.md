# Event Lifecycle

## State Machine

```
  create()
     │
     ▼
   DRAFT ──── publish() ──► PUBLISHED ──── end() ──► ENDED
                │
                └──────────── cancel() ──► CANCELLED
```

`CANCELLED` and `ENDED` are terminal states.

## Transitions

| Method | From | To | Guard | Throws |
|---|---|---|---|---|
| `create()` _(static factory)_ | _(new)_ | DRAFT | — | — |
| `publish()` | DRAFT | PUBLISHED | at least one TicketCategory exists | `EventHasNoTicketCategories` |
| `cancel()` | DRAFT or PUBLISHED | CANCELLED | — | — |
| `end()` | PUBLISHED | ENDED | — | — |

Updating event details (`updateDetails`, `reschedule`, `relocate`) is allowed from DRAFT and PUBLISHED. Adding, updating, or removing ticket categories is allowed from DRAFT and PUBLISHED — not from CANCELLED or ENDED.

## Ticket Category Management

A `TicketCategory` belongs exclusively to an `Event`. The Event aggregate root controls all mutations:

- `addTicketCategory()` — creates a new category; throws `DuplicateTicketCategoryNameForEvent` if name already exists within the event
- `updateTicketCategory()` — modifies name, price, or quantity
- `removeTicketCategory()` — throws `CannotRemoveTicketCategoryWithReservations` if `reservedQuantity > 0`

## Inventory Invariants

The `TicketCategory` child entity enforces:
- `totalQuantity > 0` — enforced in `create()` and `update()`
- `reservedQuantity <= totalQuantity` — enforced in `reserveOneTicket()` via `isSoldOut()` check
- `availableQuantity() = totalQuantity - reservedQuantity`

Reducing `totalQuantity` below `reservedQuantity` via `updateTicketCategory()` is currently not guarded by the domain — it would produce a negative `availableQuantity`. This is a known gap.

## Cancellation Cascade

When an event is cancelled, the system must also cancel pending orders and all tickets. This is handled by two listeners on `EventCancelled`:

1. `CancelOrdersOnEventCancelled` — finds all `PENDING_PAYMENT` orders for the event, calls `order.cancel()` on each → triggers `OrderCancelled` → cancels tickets → releases slots
2. `CancelTicketsOnEventCancelled` — cancels all tickets directly (catches `CONFIRMED` tickets from already-paid orders that aren't caught by the order cascade)

**Why two listeners**: Paid orders are not cancelled (they were fulfilled); their tickets need to be cancelled independently. Pending orders go through the order cancellation path to ensure consistent `OrderCancelled` events and slot release.

## Domain Events

| Event | Carries | When |
|---|---|---|
| `EventPublished` | eventId | on `publish()` |
| `EventCancelled` | eventId | on `cancel()` |
