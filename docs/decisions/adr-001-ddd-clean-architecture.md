# ADR-001 — DDD + Clean Architecture

## Context

A ticket-selling SaaS platform has complex domain rules: inventory management, payment lifecycle, concurrent reservation, event cancellation cascades. The team needs a structure that keeps business rules testable, isolated from framework concerns, and maintainable as the product grows.

## Decision

Adopt Domain-Driven Design with Clean Architecture, enforcing a strict four-layer dependency rule:

```
Domain ← Application ← Infrastructure ← UI
```

- The Domain layer is pure PHP with zero framework dependencies
- Repositories are interfaces in the Domain, implemented in Infrastructure
- Application handlers orchestrate: fetch → mutate → persist → dispatch
- No business logic lives outside the Domain layer

## Consequences

**Benefits**
- Domain logic is unit-testable without booting Symfony or hitting a database
- Swapping Doctrine for another ORM, or Stripe for another payment provider, requires changing only one class in Infrastructure
- New use cases (commands/queries) are additive — existing aggregates are not modified
- Event listeners decouple cross-aggregate side effects (e.g., confirming tickets after payment)

**Costs**
- More files per feature: Command, Handler, Interface, Result, Entity, Mapper
- New developers need to understand the layer rules before contributing
- Repository interfaces must be kept minimal — domain-meaningful methods only, no generic `findBy`

## Enforcement

The dependency rule is enforced by convention and code review, not by a static analysis tool. A future improvement would be to add `deptrac` or `phparkitect` to CI to catch violations automatically.
