# whenn-core Documentation

Backend API for the whenn ticket-selling SaaS platform.

## Quick Navigation

### Architecture
- [Overview](architecture/overview.md) — Layer map, tech choices, bounded contexts
- [Domain Model](architecture/domain-model.md) — Aggregates, value objects, repositories
- [Event-Driven Architecture](architecture/event-driven.md) — Domain events, listeners, async email

### Workflows
- [Ticket Purchase](workflows/ticket-purchase.md) — Full happy path: register → browse → order → pay → confirm
- [Ticket Check-In](workflows/ticket-check-in.md) — Staff QR scan at the event door
- [Order Refund](workflows/order-refund.md) — Refunding a paid order via Stripe
- [Order Expiry](workflows/order-expiry.md) — Automatic slot release for abandoned carts

### Business Rules
- [Order Lifecycle](business/order-lifecycle.md) — Status machine, TTL, payment reference
- [Ticket Lifecycle](business/ticket-lifecycle.md) — Status machine, entry code, inventory coupling
- [Event Lifecycle](business/event-lifecycle.md) — Status machine, category management, cancellation cascade

### Security
- [Auth and Roles](security/auth-and-roles.md) — JWT, role hierarchy, permission matrix

### Integrations
- [Stripe](integrations/stripe.md) — Checkout sessions, webhooks, refunds

### Architecture Decisions
- [ADR-001 — DDD + Clean Architecture](decisions/adr-001-ddd-clean-architecture.md)
- [ADR-002 — Synchronous Domain Events with Async Email](decisions/adr-002-event-driven-domain.md)
- [ADR-003 — Separate EntryCode for QR Check-In](decisions/adr-003-entry-code-for-checkin.md)
- [ADR-004 — Stripe Refund Before DB Update](decisions/adr-004-stripe-refund-ordering.md)

### Glossary
- [Domain Terms](glossary/domain-terms.md) — Ubiquitous language reference
