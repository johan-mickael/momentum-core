<?php

declare(strict_types=1);

namespace UI\Http\Request\Event;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'FeatureEventRequest',
    required: ['rank'],
    properties: [
        new OA\Property(property: 'rank', type: 'integer', description: 'Curated display rank — lower shows first', example: 1),
    ],
)]
final class FeatureEventRequest
{
    #[Assert\NotNull(message: 'rank is required.')]
    #[Assert\Positive(message: 'rank must be a positive integer.')]
    public ?int $rank = null;
}
