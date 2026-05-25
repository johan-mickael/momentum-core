# Ticket Lifecycle

## State Machine

```
   reserve()
      │
      ▼
  RESERVED ─── cancel() ──► CANCELLED
      │
  confirm()
      │
      ▼
 CONFIRMED ─── cancel() ──► CANCELLED
      │
 markAsUsed()
      │
      ▼
    USED
```

`CANCELLED` and `USED` are terminal states.

## Transitions

| Method | From | To | Guard | Throws |
|---|---|---|---|---|
| `reserve()` _(static factory)_ | _(new)_ | RESERVED | — | — |
| `confirm()` | RESERVED | CONFIRMED | status is RESERVED | `InvalidTicketStatusTransition::cannotConfirm()` |
| `cancel()` | RESERVED or CONFIRMED | CANCELLED | status is not CANCELLED or USED | `InvalidTicketStatusTransition::cannotCancel()` |
| `markAsUsed()` | CONFIRMED | USED | status is CONFIRMED | `InvalidTicketStatusTransition::cannotMarkAsUsed()` |

## Who Changes Status

| Transition | Triggered By |
|---|---|
| RESERVED → CONFIRMED | `ConfirmTicketsOnOrderPaid` listener (on `OrderPaid`) |
| CONFIRMED → USED | `CheckInTicketCommandHandler` (via `POST /tickets/check-in`) |
| ANY → CANCELLED | `CancelTicketsOnOrder{Expired,Cancelled,Refunded}` or `CancelTicketsOnEventCancelled` |

Buyers cannot directly change ticket status via the API. Confirmation and used-marking are system-driven or staff-driven.

## EntryCode

`EntryCode` is a UUID generated at `reserve()` time by `PlaceOrderCommandHandler` using `IdGeneratorInterface::generate()`. It is:
- Separate from `TicketId` — the internal DB key is never exposed in a QR code
- Immutable — it cannot be changed after creation
- Unique per ticket — enforced by a `UNIQUE INDEX` on the `entry_code` column
- The value that is encoded in the attendee's QR code

See `docs/workflows/ticket-check-in.md` for the check-in flow.

## Inventory Coupling

`Ticket` and `TicketCategory` are kept in sync via domain events:

- `Ticket::reserve()` is always preceded by `Event::reserveTicketInCategory()` in the same handler — both happen atomically
- `Ticket::cancel()` emits `TicketCancelled`, which triggers `ReleaseTicketSlotOnTicketCancelled` → `Event::releaseTicketInCategory()`

The inventory count (`reservedQuantity` on `TicketCategory`) is never directly modified from a Ticket handler — the Event aggregate controls its own invariants.

## Domain Events

| Event | Carries |
|---|---|
| `TicketReserved` | ticketId, eventId, categoryId, holderId |
| `TicketConfirmed` | ticketId, eventId, categoryId, holderId |
| `TicketCancelled` | ticketId, eventId, categoryId |
| `TicketUsed` | ticketId, eventId |
