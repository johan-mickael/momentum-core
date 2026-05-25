# Domain Glossary

This glossary defines the ubiquitous language used throughout the codebase. When in doubt about naming, prefer these terms over generic CRUD names.

## Core Terms

**Event**
A concert, festival, performance, or any time-bound gathering for which tickets are sold. An Event is owned by an organizer (staff/admin). It has ticket categories, a venue, and a date range.

**Ticket Category**
A class of admission within an Event (e.g., "General Admission", "VIP", "Backstage"). Each category has a price and a fixed total quantity. Multiple tickets can be reserved from the same category.

**Venue**
A physical location where an Event is held. Has an address, optional geo-coordinates, and a capacity. Managed by staff.

**Order**
A buyer's intent to purchase one or more tickets. An Order groups multiple tickets from one Event, has a total price, and expires 15 minutes after placement if not paid.

**Ticket**
A single right of admission for one person. Always belongs to exactly one Order and one Ticket Category. A Ticket has an `entryCode` for QR scanning at the door.

**Entry Code**
An opaque UUID associated with a Ticket. Encoded in the QR code shown to the attendee. Used by staff to check in the attendee via `POST /tickets/check-in`. Not the same as the Ticket's internal ID.

**Payment Reference**
The Stripe `payment_intent` ID stored on a paid Order. Used as the handle to issue a Stripe refund.

**Check-in**
The act of scanning a ticket's entry code at the event entrance. Changes ticket status from `CONFIRMED` to `USED`. Performed by staff.

**Reserve**
The act of locking a ticket slot when an Order is placed. Decrements `availableQuantity` on the Ticket Category. Reservation is temporary — slots are released if the Order expires or is cancelled without payment.

**Confirm**
Transitioning a ticket from `RESERVED` to `CONFIRMED` after the Order is paid. Triggered automatically by the `ConfirmTicketsOnOrderPaid` listener.

**Expire**
Automatic cancellation of a `PENDING_PAYMENT` Order (and its tickets) 15 minutes after placement if payment was not received. Triggered by the `ExpireOrdersTask` scheduler.

**Refund**
Full return of the payment for a `PAID` Order. Calls Stripe to reverse the charge, cancels all tickets, releases inventory slots.

## Status Terms

**DRAFT** — An Event not yet visible to buyers.

**PUBLISHED** — An Event visible to buyers with tickets available for purchase.

**PENDING_PAYMENT** — An Order placed but not yet paid. Expires after 15 minutes.

**PAID** — An Order whose payment has been confirmed via Stripe webhook.

**RESERVED** — A Ticket whose slot is held but payment is not yet confirmed.

**CONFIRMED** — A Ticket that is fully paid and valid for entry.

**USED** — A Ticket that has been scanned at the door. Terminal state.

**CANCELLED** — An Order or Ticket that has been voided. Terminal state.

## Role Terms

**Buyer** — A registered user who can place and pay for orders. Default role for new registrations.

**Staff** — An employee who can manage events, venues, and perform check-in. Can also refund orders.

**Admin** — Full access including user management. Inherits all Staff permissions.

## Patterns

**Aggregate Root** — The single entry point for mutations on an aggregate. External code never modifies a child entity directly.

**Value Object** — An immutable object defined by its value rather than identity. Examples: `Money`, `Email`, `EntryCode`, `Address`.

**Domain Event** — An immutable record of something that happened. Named in past tense. Examples: `OrderPaid`, `TicketConfirmed`, `EventCancelled`.

**Use Case** — A single application operation represented as a Command or Query + Handler pair.
