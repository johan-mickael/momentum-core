# Architecture Overview

## Purpose

whenn-core is the backend API for a ticket-selling SaaS platform. It exposes a JSON REST API consumed by frontends and processes payment events from Stripe.

## Architectural Style

The codebase follows **DDD (Domain-Driven Design)** combined with **Clean Architecture**. All code lives in one of four layers with strict dependency rules.

## Layer Map

```
Domain          ← no dependencies on anything in this project
   ↑
Application     ← depends only on Domain
   ↑
Infrastructure  ← depends on Application + Domain, implements their interfaces
   ↑
Presentation    ← depends on Application (Commands, Queries, Results)
```

The dependency rule is enforced manually. Nothing in Domain may import from Application, Infrastructure, or Presentation. Nothing in Application may import from Infrastructure or Presentation.

## Directory Structure

```
src/
├── Domain/         ← Pure PHP, zero framework dependencies
│   ├── Common/     ← Shared value objects, interfaces, traits
│   ├── Event/      ← Event aggregate (concerts, festivals, etc.)
│   ├── Order/      ← Order aggregate
│   ├── Ticket/     ← Ticket aggregate
│   ├── User/       ← User aggregate
│   └── Venue/      ← Venue aggregate
│
├── Application/    ← Use cases: Commands, Queries, Event Listeners
│   ├── Auth/
│   ├── Event/
│   ├── Order/
│   ├── Ticket/
│   └── Venue/
│
├── Infrastructure/ ← Symfony + Doctrine implementations
│   ├── Bus/
│   ├── Common/
│   ├── Event/
│   ├── Notification/
│   ├── Payment/
│   ├── Persistence/
│   ├── Scheduler/
│   └── Security/
│
└── UI/             ← HTTP controllers, console commands
    ├── Console/
    └── Http/
```

## Key Technology Choices

| Concern | Solution |
|---|---|
| Framework | Symfony 8.0 (FrankenPHP) |
| ORM | Doctrine ORM 3.x |
| Authentication | JWT via LexikJwtAuthenticationBundle |
| Async messaging | Symfony Messenger + AMQP (RabbitMQ) |
| Task scheduling | Symfony Scheduler |
| Payment | Stripe Checkout Sessions |
| Email | Symfony Mailer (async via Messenger) |
| Database | PostgreSQL 16 |
| PHP | >= 8.4 |

## Bounded Contexts

The application is a single bounded context. Five aggregates represent the core domain:

| Aggregate | Responsibility |
|---|---|
| **User** | Identity, authentication, role management |
| **Venue** | Physical location where events are held |
| **Event** | The concert or show — owns ticket categories and capacity |
| **Order** | A buyer's intent to purchase, plus payment lifecycle |
| **Ticket** | A confirmed right of admission for one person |

See `docs/architecture/domain-model.md` for aggregate details.

## Communication Patterns

### Synchronous
HTTP request → Controller → Command/Query → Handler → Repository → Response

### Asynchronous
Domain event raised → `DomainEventDispatcherInterface` → Symfony EventDispatcher → Application listener → side effect (confirm tickets, send email, release slots)

Email specifically is routed through Symfony Messenger to AMQP so that mailer failures never block a request.

See `docs/architecture/event-driven.md` for the full event map.

## CQRS

Commands mutate state. Queries read state and never produce side effects. They share no base class — the distinction is enforced by convention and code review.

Result objects (e.g., `TicketResult`, `OrderResult`) are immutable DTOs returned by query handlers. Entities are never returned to the UI layer.

## Authorization

All authorization is policy-based. Each aggregate has a `{Name}Policy` class implementing `PolicyInterface`. The `AuthorizationService` iterates all registered policies and delegates to the first matching one.

Handlers call `$authorizationService->authorize($actor, $action, $context)` before mutating state. Unauthorized access throws a domain exception that the HTTP exception listener converts to a 403 response.

See `docs/security/auth-and-roles.md` for the full permission matrix.
