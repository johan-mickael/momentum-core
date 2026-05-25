# Stripe Integration

## Overview

Stripe is used for payment processing via **Checkout Sessions** (hosted payment page). The integration covers:

1. Creating a checkout session when the buyer initiates payment
2. Receiving webhook confirmation when payment completes
3. Issuing refunds when an order is cancelled post-payment

## Environment Variables

| Variable | Purpose |
|---|---|
| `STRIPE_SECRET_KEY` | Server-side Stripe API key |
| `STRIPE_WEBHOOK_SECRET` | HMAC secret for webhook signature verification |
| `STRIPE_CHECKOUT_SUCCESS_URL` | Redirect URL after successful payment |
| `STRIPE_CHECKOUT_CANCEL_URL` | Redirect URL if buyer cancels on Stripe's page |

## Checkout Session Creation

**Endpoint**: `POST /orders/{orderId}/checkout`

**Handler**: `InitiateCheckoutCommandHandler` → `StripeCheckoutService::createSession(Order)`

```
StripeCheckoutService builds:
  mode: "payment"
  line_items: grouped by categoryId
    - price_data.currency: order currency (lowercase)
    - price_data.unit_amount: price in cents
    - price_data.product_data.name: "Ticket — {categoryId}"
    - quantity: number of tickets in that category
  success_url: STRIPE_CHECKOUT_SUCCESS_URL
  cancel_url: STRIPE_CHECKOUT_CANCEL_URL
  metadata:
    orderId: {orderId}   ← used by webhook to link back to the order
```

The `checkoutUrl` from the session is returned to the caller who redirects the buyer.

**Note**: The `product_data.name` currently uses the category UUID. This should be replaced with the category name for better buyer UX (see decisions/future improvements).

## Webhook: checkout.session.completed

**Endpoint**: `POST /stripe/webhook`

**Security**: The `Stripe-Signature` header is validated using `Webhook::constructEvent($payload, $signature, $secret)`. An invalid signature throws `WebhookSignatureInvalid` → HTTP 400.

**Processing**:

```
1. Verify signature
2. Assert event.type === "checkout.session.completed"
3. Extract orderId = event.data.object.metadata.orderId
4. Extract paymentIntent = event.data.object.payment_intent
5. MarkOrderAsPaidCommand(orderId, paidAt, paymentReference: paymentIntent)
```

**Idempotency**: Stripe delivers at-least-once. `OrderAlreadyPaid` and `CannotPayCancelledOrder` are caught in the webhook handler and silently ignored, returning HTTP 200 so Stripe stops retrying.

**Ignored events**: Any `event.type` other than `checkout.session.completed` is silently ignored with HTTP 204 (no error returned, which would cause unnecessary Stripe retries).

## Refunds

**Endpoint**: `POST /orders/{orderId}/refund`

**Handler**: `RefundOrderCommandHandler` → `StripeRefundService::refund(PaymentReference)`

```php
$this->stripe->refunds->create([
    'payment_intent' => $paymentReference->toString(),
]);
```

This issues a full refund of the original charge. Partial refunds are not supported.

The `payment_intent` ID is stored on the `Order` as a `PaymentReference` when `markAsPaid()` is called from the webhook handler. If it is `null`, the refund is blocked with `OrderHasNoPaymentReference`.

## Stripe Client Configuration

The `StripeClient` is instantiated in `config/packages/stripe.yaml` with `STRIPE_SECRET_KEY`. It is injected into infrastructure services via Symfony's DI.

```yaml
# config/packages/stripe.yaml
services:
    Stripe\StripeClient:
        arguments:
            - '%env(STRIPE_SECRET_KEY)%'
```

## Webhook Registration

Register the webhook in the Stripe dashboard:
- URL: `https://your-domain/stripe/webhook`
- Events: `checkout.session.completed`
- Copy the signing secret to `STRIPE_WEBHOOK_SECRET`

For local development, use the Stripe CLI:
```bash
stripe listen --forward-to localhost/stripe/webhook
```

The CLI prints a local webhook secret — set it as `STRIPE_WEBHOOK_SECRET` in `.env.local`.

## Stripe CLI listener (dev)

```bash
bin/console app:stripe:listen
```

The `StripeListenCommand` wraps the Stripe CLI listener for convenience in development.

## API Version

The Stripe PHP SDK version is `^20.1` (defined in `composer.json`). The Stripe API version is pinned by the SDK version. Upgrading the SDK may change the API version — test webhook payload structure when upgrading.
