# Workflow: Ticket Purchase (Happy Path)

## Trigger

A buyer finds a published event and wants to attend.

## Actors

- **Buyer** — authenticated user with role BUYER or higher
- **Stripe** — external payment processor
- **System** — background workers and event listeners

## Preconditions

- The buyer is registered and holds a valid JWT
- The event is in `PUBLISHED` status
- The ticket category has available slots

## Main Flow

### Step 1 — Register and log in

```
POST /auth/register
{
  "email": "buyer@example.com",
  "password": "...",
  "firstName": "...",
  "lastName": "..."
}
→ 201 { id, email, role: "BUYER", firstName, lastName }

POST /auth/login
{ "email": "...", "password": "..." }
→ 200 { "token": "<jwt>" }
```

All subsequent requests include `Authorization: Bearer <jwt>`.

### Step 2 — Browse events

```
GET /events
→ 200 [ { id, name, venueId, slug, startAt, endAt, status, description, imageUrl } ]

GET /events/{eventId}
→ 200 { id, name, ..., status: "PUBLISHED" }

GET /events/{eventId}/ticket-categories
→ 200 [ { id, name, priceAmount, priceCurrency, totalQuantity, availableQuantity } ]
```

### Step 3 — Place order

The order atomically reserves the requested ticket slots. Slots are unavailable to other buyers while the order is pending.

```
POST /orders
{
  "eventId": "<eventId>",
  "items": [
    { "categoryId": "<categoryId>", "quantity": 2 }
  ]
}
→ 201 {
  "id": "<orderId>",
  "status": "pending_payment",
  "totalAmount": 10000,
  "currency": "USD",
  "expiresAt": "2026-05-25T12:30:00Z",
  "items": [
    { "categoryId": "...", "ticketId": "...", "unitPriceAmount": 5000, "unitPriceCurrency": "USD" },
    { "categoryId": "...", "ticketId": "...", "unitPriceAmount": 5000, "unitPriceCurrency": "USD" }
  ]
}
```

**What happens inside**:
1. `PlaceOrderCommandHandler` fetches the `Event`
2. For each item/quantity: `event.reserveTicketInCategory()` + `Ticket::reserve()` (status: RESERVED)
3. `Order::place()` (status: PENDING_PAYMENT, expiresAt: now + 15 min)
4. All saved in one transaction
5. `OrderPlaced` and `TicketReserved` domain events dispatched

If any category is sold out, `CannotReserveTicketInCategory` is thrown and the whole operation rolls back — no partial reservations.

### Step 4 — Initiate Stripe checkout

```
POST /orders/{orderId}/checkout
→ 200 { "checkoutUrl": "https://checkout.stripe.com/pay/cs_..." }
```

**What happens inside**:
1. `InitiateCheckoutCommandHandler` verifies the order is `PENDING_PAYMENT` and the actor is the buyer
2. `StripeCheckoutService::createSession()` builds line items grouped by category
3. Stripe creates a hosted checkout session with `metadata.orderId = orderId`
4. The session URL is returned

The buyer is redirected to Stripe's hosted page.

### Step 5 — Payment on Stripe

The buyer enters card details on Stripe's hosted page. Stripe processes the charge.

### Step 6 — Webhook: payment confirmed

Stripe sends `POST /stripe/webhook` with event `checkout.session.completed`.

```
StripePaymentWebhookHandler:
  1. Validate HMAC signature using STRIPE_WEBHOOK_SECRET
  2. Extract orderId from session.metadata.orderId
  3. Extract payment_intent from session.payment_intent
  4. Call MarkOrderAsPaidCommand(orderId, paidAt, paymentReference)
```

**What happens inside MarkOrderAsPaid**:
1. `order.markAsPaid(paidAt, PaymentReference)` → status: PAID
2. `PaymentReference` (the Stripe payment_intent ID) is stored on the order for future refunds
3. `OrderPaid` event dispatched

**Idempotency**: `OrderAlreadyPaid` and `CannotPayCancelledOrder` exceptions are caught and silently ignored. Stripe delivers webhooks at-least-once.

### Step 7 — Tickets confirmed (async, same request)

`ConfirmTicketsOnOrderPaid` listener fires synchronously on `OrderPaid`:

```
For each ticketId in order.items:
  ticket.confirm() → status: RESERVED → CONFIRMED
  TicketConfirmed dispatched
```

### Step 8 — Confirmation email (async, worker)

`SendConfirmationEmailOnOrderPaid` calls `SymfonyOrderNotificationService::sendOrderConfirmation()`, which sends a `SendEmailMessage` to the Messenger async queue. A worker picks it up and delivers the email.

### Step 9 — Buyer receives ticket details

```
GET /tickets/me
→ 200 [ { id, status: "confirmed", entryCode: "...", eventId, ... } ]

GET /orders/me
→ 200 [ { id, status: "paid", paidAt, items[], totalAmount, ... } ]
```

The `entryCode` field is the value to encode in a QR code for event entry.

## Failure Cases

| Scenario | Behaviour |
|---|---|
| Category sold out | `PlaceOrderCommand` throws `CannotReserveTicketInCategory` → 422 |
| Order expires before payment | `ExpireOrdersTask` cancels the order, releases slots |
| Buyer pays after expiry | Webhook fires `OrderAlreadyPaid` catch → 200 to Stripe (no retry), slots already released |
| Stripe signature invalid | `WebhookSignatureInvalid` → 400; Stripe retries |
| Email delivery fails | Worker retries via Messenger; falls to dead-letter queue after exhaustion |

## Postconditions

- Order status: `PAID`
- All tickets status: `CONFIRMED`
- Each ticket has an `entryCode` for QR check-in
- `PaymentReference` stored on order (Stripe `payment_intent` ID)
- `TicketCategory.reservedQuantity` permanently incremented (slots consumed)
- Confirmation email queued for delivery

## Side Effects

- Stripe charge created
- Email queued in AMQP
- Ticket slot inventory updated in DB
