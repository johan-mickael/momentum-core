# ADR-003 — Separate EntryCode for QR Check-In

## Context

Tickets need to be scannable at the event door. The naive approach would be to encode the ticket's UUID (`TicketId`) in the QR code and use `POST /tickets/{ticketId}/mark-as-used` for check-in.

## Problem

`TicketId` is the database primary key. It is:
- Returned in API responses to admins and staff
- Potentially visible in logs and monitoring
- Used in internal URLs and queries

Exposing it in a QR code means anyone who learns the ticket UUID from any internal source can fabricate a scan for that ticket.

## Decision

Generate a separate `EntryCode` UUID at ticket reservation time, independent of the ticket's primary key. The `EntryCode` is:
- Never equal to `TicketId`
- Stored in its own column with a `UNIQUE INDEX`
- Looked up via `TicketRepositoryInterface::findByEntryCode(EntryCode)`
- Returned in `TicketResult.entryCode` — the field buyers use to generate their QR code

A dedicated endpoint `POST /tickets/check-in` accepts `{ "entryCode": "..." }` instead of a path parameter, making it clear the caller is submitting a scan code, not a ticket identifier.

## Consequences

**Benefits**
- Internal ticket IDs are never encoded in QR codes
- A compromised ticket UUID does not allow unauthorized check-in
- The entry code can be rotated (for future invalidation/reissue feature) without changing the ticket identity

**Costs**
- Extra UUID generated per ticket
- Extra `entry_code` column in the `ticket` table
- A dedicated find-by-entry-code repository method instead of reusing `findById`

## Alternative Rejected

Sign the `TicketId` with an HMAC using a server-side secret and encode both in the QR code. Verification would recompute the HMAC. This was rejected because it adds cryptographic complexity without meaningful benefit — the `EntryCode` UUID approach achieves the same security goal (unguessable code) with simpler implementation.
