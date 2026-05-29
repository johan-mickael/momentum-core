<?php

declare(strict_types=1);

namespace App\Domain\Common\ValueObject;

enum SortDirection: string
{
    case ASC  = 'ASC';
    case DESC = 'DESC';

    public static function fromString(string $value): self
    {
        return self::tryFrom(strtoupper($value)) ?? self::ASC;
    }
}
