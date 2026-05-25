# Domain Model

## Aggregates

### User

**Root**: `Domain/User/User.php`

Represents a registered person. Owns identity, credentials, and role.

**State**: No status enum — a User is always active once created.

**Key Value Objects**
- `UserId` — UUID wrapper
- `Email` — validated email address; throws `InvalidEmail` if malformed
- `PlainPassword` — minimum 8 characters; throws on violation

**Role Hierarchy**

```
ADMIN ⊃ STAFF ⊃ BUYER ⊃ USER
```

ADMIN inherits all lower roles. `$role->isStaff()` returns true for ADMIN and STAFF.

**Domain Events**
- `UserRegistered(userId, email)` — fired on `User::register()`

**Repository**: `UserRepositoryInterface`
- `findById(string): ?User`
- `findByEmail(string): ?User`
- `save(User): void`

---

### Venue

**Root**: `Domain/Venue/Venue.php`

A physical location. Owned by staff/admin. Buyers only see it through Event listings.

**Key Value Objects**
- `VenueId` — UUID wrapper
- `Address(street, city, zipCode, country)` — all fields required; throws `InvalidAddress`
- `GeoLocation(latitude, longitude)` — latitude in [-90, 90], longitude in [-180, 180]
- `Capacity(int)` — must be > 0; throws `InvalidVenueCapacity`

**Domain Services**
- `VenueNameUniquenessChecker` — throws `DuplicateVenueName` if name already exists
- `VenueAddressUniquenessChecker` — throws `DuplicateVenueAddress` if address already exists

**Repository**: `VenueRepositoryInterface`
- `listVenues(): Venue[]`
- `getById(VenueId): Venue`
- `findByName(string): ?Venue`
- `findByAddress(Address): ?Venue`

---

### Event

**Root**: `Domain/Event/Event.php`

A concert, festival, or show. Manages its own ticket inventory via `TicketCategory` children.

**Status Machine**

```
DRAFT ──publish()──► PUBLISHED ──end()──► ENDED
                │
                └──cancel()──► CANCELLED
```

`publish()` requires at least one `TicketCategory`. Throws `EventHasNoTicketCategories` otherwise.
Cancelled and Ended are terminal — no further transitions.

**Child Entity**: `TicketCategory`

Each category tracks its own inventory:
- `totalQuantity` — set at creation
- `reservedQuantity` — incremented on `Event::reserveTicketInCategory()`, decremented on `Event::releaseTicketInCategory()`
- `availableQuantity()` = `totalQuantity - reservedQuantity`

`reserveOneTicket()` throws `CannotReserveTicketInCategory` when `availableQuantity() === 0`.

**Key Value Objects**
- `EventId` — UUID wrapper
- `EventSlug` — regex `^[a-z0-9]+(?:-[a-z0-9]+)*$`; throws `InvalidEventSlug`
- `DateRange(startAt, endAt)` — endAt must be > startAt; throws `InvalidDateRange`
- `TicketCategoryId` — UUID wrapper
- `Money(amount, currency)` — amount in cents, must be >= 0

**Domain Events**
- `EventPublished(eventId)` — fired on `Event::publish()`
- `EventCancelled(eventId)` — fired on `Event::cancel()`

**Repository**: `EventRepositoryInterface`
- `getById(EventId): Event`
- `list(EventVisibility): Event[]`
- `save(Event): void`

---

### Order

**Root**: `Domain/Order/Order.php`

Represents a buyer's intent to purchase one or more tickets. Created atomically with ticket reservations. Has a 15-minute TTL when unpaid.

**Status Machine**

```
PENDING_PAYMENT ──markAsPaid()──► PAID ──refund()──► CANCELLED
       │
       ├──cancel()──► CANCELLED
       └──expire()──► CANCELLED
```

`cancel()` only works from `PENDING_PAYMENT`. Silently returns if already cancelled or paid — callers must use `refund()` for paid orders.

`refund()` only works from `PAID`. Throws `CannotRefundOrder::notPaid()` otherwise.

`markAsPaid()` throws `OrderAlreadyPaid` if already paid, `CannotPayCancelledOrder` if cancelled.

**TTL**: Orders expire 15 minutes after placement (`TTL_MINUTE = 15`). The `ExpireOrdersTask` runs every 60 seconds to cancel expired pending orders and release their ticket slots.

**Key Value Objects**
- `OrderId` — UUID wrapper
- `PaymentReference` — wraps the Stripe `payment_intent` ID; non-empty; set when `markAsPaid()` is called with a reference; required to call `refund()`
- `OrderItem(categoryId, ticketId, unitPrice)` — immutable, one per ticket reserved

**Domain Events**
- `OrderPlaced(orderId, buyerId, eventId, ticketIds[])` — fired on `Order::place()`
- `OrderPaid(orderId, ticketIds[])` — fired on `Order::markAsPaid()`
- `OrderCancelled(orderId, ticketIds[])` — fired on `Order::cancel()`
- `OrderExpired(orderId, ticketIds[])` — fired on `Order::expire()`
- `OrderRefunded(orderId, ticketIds[], paymentReference)` — fired on `Order::refund()`

**Repository**: `OrderRepositoryInterface`
- `getById(OrderId): Order`
- `findPendingExpiredBefore(DateTimeImmutable): Order[]`
- `findPendingByEventId(EventId): Order[]`
- `findByBuyerId(UserId): Order[]`
- `save(Order): void`

---

### Ticket

**Root**: `Domain/Ticket/Ticket.php`

A single right of admission for one person. Created during `PlaceOrder`. Confirmed after payment. Marked as used at event entry.

**Status Machine**

```
RESERVED ──confirm()──► CONFIRMED ──markAsUsed()──► USED
    │                       │
    └──cancel()──────────────┴──cancel()──► CANCELLED
```

`confirm()` requires `RESERVED` status. `markAsUsed()` requires `CONFIRMED`. `cancel()` works from any status except `CANCELLED` or `USED`.

**Key Value Objects**
- `TicketId` — UUID wrapper
- `EntryCode` — a separate UUID generated at reservation time; used for QR-code check-in; not the same as `TicketId` so the internal ID is never exposed in a scannable code

**Why EntryCode is separate from TicketId**: The ticket UUID is an internal database identifier. Exposing it in a QR code would allow someone who learns the UUID to check in on behalf of the holder. `EntryCode` is a separate opaque random value, making the QR code unguessable from any other system identifier.

**Domain Events**
- `TicketReserved(ticketId, eventId, categoryId, holderId)` — fired on `Ticket::reserve()`
- `TicketConfirmed(ticketId, eventId, categoryId, holderId)` — fired on `Ticket::confirm()`
- `TicketCancelled(ticketId, eventId, categoryId)` — fired on `Ticket::cancel()`
- `TicketUsed(ticketId, eventId)` — fired on `Ticket::markAsUsed()`

**Repository**: `TicketRepositoryInterface`
- `findById(TicketId): Ticket` — throws `TicketNotFound`
- `findByEntryCode(EntryCode): Ticket` — throws `TicketNotFoundByEntryCode`
- `findByEvent(EventId): Ticket[]`
- `findByHolder(UserId): Ticket[]`
- `save(Ticket): void`

---

## Shared Domain Infrastructure

### `RecordsDomainEvents` Trait

All aggregates use this trait to buffer domain events internally. Events are not dispatched during the mutation — they are pulled and dispatched by the Application layer after persistence.

```php
$order->markAsPaid($paidAt, $ref);       // event buffered inside aggregate
$this->orders->save($order);             // persisted
$this->dispatcher->dispatch(            // events dispatched
    ...$order->pullRecordedEvents()
);
```

This ensures events are only raised when the state change is safely persisted.

### `Money` Value Object

All prices and totals use `Money(int $amount, Currency $currency)`. Amount is always in cents (smallest currency unit) to avoid floating-point rounding. `Money::add()` throws `CurrencyMismatch` if currencies differ.
