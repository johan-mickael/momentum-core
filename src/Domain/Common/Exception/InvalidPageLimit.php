<?php

declare(strict_types=1);

namespace Domain\Common\Exception;

final class InvalidPageLimit extends \DomainException
{
    public function __construct(int $limit, int $max)
    {
        parent::__construct(sprintf('Page limit must be between 1 and %d, got %d.', $max, $limit));
    }
}
