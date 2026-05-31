<?php

declare(strict_types=1);

namespace UI\Http\Request\Event;

use Domain\Event\ValueObject\EventSlug;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'CreateEventRequest',
    required: ['name', 'venueId', 'eventSlug', 'startAt', 'endAt'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Symphonie 2026'),
        new OA\Property(property: 'venueId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'eventSlug', type: 'string', description: 'Lowercase alphanumeric with hyphens', example: 'symphonie-2026'),
        new OA\Property(property: 'startAt', type: 'string', format: 'date-time', example: '2026-06-15T19:00:00+02:00'),
        new OA\Property(property: 'endAt', type: 'string', format: 'date-time', example: '2026-06-15T22:00:00+02:00'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'imageUrl', type: 'string', format: 'uri', nullable: true),
    ],
)]
final class CreateEventRequest
{
    #[Assert\NotBlank(message: 'name is required.')]
    public ?string $name = null;

    #[Assert\NotBlank(message: 'venue id is required.')]
    #[Assert\Uuid(message: 'venue id must be a valid UUID.')]
    public ?string $venueId = null;

    #[Assert\NotBlank(message: 'slug is required.')]
    #[Assert\Regex(
        pattern: EventSlug::EVENT_SLUG_PATTERN,
        message: 'slug must be lowercase alphanumeric with hyphens only.'
    )]
    public ?string $eventSlug = null;

    #[Assert\NotBlank(message: 'start date is required.')]
    #[Assert\DateTime(format: \DateTimeInterface::ATOM, message: 'start date must be a valid ISO 8601 date.')]
    public ?string $startAt = null;

    #[Assert\NotBlank(message: 'end date is required.')]
    #[Assert\DateTime(format: \DateTimeInterface::ATOM, message: 'end date must be a valid ISO 8601 date.')]
    public ?string $endAt = null;

    public ?string $description = null;

    #[Assert\Url(message: 'Image url is required.')]
    public ?string $imageUrl = null;
}
