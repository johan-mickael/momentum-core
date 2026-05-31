<?php

declare(strict_types=1);

namespace UI\Http\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Auth', description: 'Registration, login, and current user profile')]
#[OA\Tag(name: 'Venues', description: 'Venue management (STAFF only)')]
#[OA\Tag(name: 'Events', description: 'Event lifecycle management')]
#[OA\Tag(name: 'Ticket Categories', description: 'Ticket category CRUD within an event (STAFF only)')]
#[OA\Tag(name: 'Tickets', description: 'Ticket reservation and lifecycle')]
#[OA\Tag(name: 'Orders', description: 'Order placement, checkout, and management')]
#[OA\Tag(name: 'Webhooks', description: 'External service webhooks (no JWT auth)')]
final class ApiDocumentation
{
}
