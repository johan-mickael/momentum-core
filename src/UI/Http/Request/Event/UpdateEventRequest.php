<?php

declare(strict_types=1);

namespace App\UI\Http\Request\Event;

use DateTimeInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'UpdateEventRequest',
    required: ['name', 'venueId', 'startAt', 'endAt'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Symphonie 2026 — Édition Prestige'),
        new OA\Property(property: 'venueId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'startAt', type: 'string', format: 'date-time', example: '2026-06-15T20:00:00+02:00'),
        new OA\Property(property: 'endAt', type: 'string', format: 'date-time', example: '2026-06-15T23:30:00+02:00'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'imageUrl', type: 'string', format: 'uri', nullable: true),
    ],
)]
final class UpdateEventRequest
{
    #[Assert\NotBlank(message: 'name is required.')]
    public ?string $name = null;

    #[Assert\NotBlank(message: 'venue id is required.')]
    #[Assert\Uuid(message: 'venue id must be a valid UUID.')]
    public ?string $venueId = null;

    #[Assert\NotBlank(message: 'start date is required.')]
    #[Assert\DateTime(format: DateTimeInterface::ATOM, message: 'start date must be a valid ISO 8601 date.')]
    public ?string $startAt = null;

    #[Assert\NotBlank(message: 'end date is required.')]
    #[Assert\DateTime(format: DateTimeInterface::ATOM, message: 'end date must be a valid ISO 8601 date.')]
    public ?string $endAt = null;

    public ?string $description = null;

    #[Assert\Url(message: 'imageUrl must be a valid URL.')]
    public ?string $imageUrl = null;
}
