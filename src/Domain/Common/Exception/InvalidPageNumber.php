<?php

declare(strict_types=1);

namespace Domain\Common\Exception;

final class InvalidPageNumber extends \DomainException
{
    public function __construct(int $page)
    {
        parent::__construct(sprintf('Page number must be at least 1, got %d.', $page));
    }
}
