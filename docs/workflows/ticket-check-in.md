# Workflow: Ticket Check-In

## Trigger

An attendee arrives at the event venue and presents their ticket QR code for scanning.

## Actors

- **Attendee** — holds a CONFIRMED ticket with an entry code
- **Staff** — authenticated user with role STAFF or ADMIN, operating a scanning device

## Preconditions

- The ticket is in `CONFIRMED` status
- The staff member is authenticated with a valid JWT (role STAFF or ADMIN)
- The scanning device has the staff member's JWT stored

## Entry Code Design

Each ticket carries two identifiers:

| Identifier | Purpose | Exposed? |
|---|---|---|
| `TicketId` | Internal database primary key | Never shown in QR |
| `EntryCode` | Opaque UUID for check-in scanning | Shown in QR code |

The `EntryCode` is generated independently (not derived from `TicketId`) at reservation time using `IdGeneratorInterface`. This prevents an attacker who learns the `TicketId` from one source from constructing a valid check-in code.

The buyer sees `entryCode` in `GET /tickets/me` and `GET /tickets/{id}`. The QR code encodes only the `entryCode` string.

## Main Flow

### Step 1 — Buyer presents QR code

The attendee shows the QR code on their device. The QR encodes the `entryCode` UUID string.

### Step 2 — Staff scans

The scanning app sends:

```
POST /tickets/check-in
Authorization: Bearer <staff-jwt>
{
  "entryCode": "550e8400-e29b-41d4-a716-446655440001"
}
→ 204 No Content
```

### Step 3 — System processes check-in

`CheckInTicketCommandHandler`:
1. `TicketRepository::findByEntryCode(EntryCode)` — throws `TicketNotFoundByEntryCode` if unknown
2. `AuthorizationService::authorize(actor, Action::UPDATE, TicketContext(ticket))` — requires STAFF role
3. `ticket.markAsUsed()` — status: CONFIRMED → USED; throws `InvalidTicketStatusTransition` if not CONFIRMED
4. `TicketRepository::save(ticket)`
5. `TicketUsed` domain event dispatched
6. Transaction flushed

### Step 4 — Response

`204 No Content` — the scanning app signals entry is allowed.

## Failure Cases

| Scenario | Response | Meaning |
|---|---|---|
| Unknown entry code | 404 — `TicketNotFoundByEntryCode` | Fake or corrupted QR |
| Ticket already used | 422 — `InvalidTicketStatusTransition` | Duplicate scan attempt |
| Ticket cancelled | 422 — `InvalidTicketStatusTransition` | Ticket was refunded or cancelled |
| Ticket still RESERVED | 422 — `InvalidTicketStatusTransition` | Payment not completed |
| Actor not STAFF | 403 — `MarkTicketAsUsedForbidden` | Scanner is not authorized |

## Postconditions

- Ticket status: `USED` (terminal — cannot be scanned again)
- `TicketUsed` domain event recorded

## Business Rules

- A ticket can only be scanned once. The `USED` status is terminal.
- Only staff can perform check-in. Buyers cannot call this endpoint on their own tickets.
- The `entryCode` is validated by database lookup, not by any cryptographic scheme. The randomness of the UUID (128 bits) makes brute-forcing infeasible.
- There is no time-window enforcement at the API level — the system does not reject check-ins that happen before or after the event window. This is an operational decision.
