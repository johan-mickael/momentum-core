<?php

declare(strict_types=1);

namespace App\Domain\Common\ValueObject;

use App\Domain\Common\Exception\InvalidPageLimit;
use App\Domain\Common\Exception\InvalidPageNumber;

final readonly class PaginationCriteria
{
    public const int DEFAULT_PAGE = 1;
    public const int DEFAULT_LIMIT = 20;
    public const int MAX_LIMIT = 100;

    public function __construct(
        public readonly int $page,
        public readonly int $limit,
    ) {
        if ($this->page < 1) {
            throw new InvalidPageNumber($this->page);
        }
        if ($this->limit < 1 || $this->limit > self::MAX_LIMIT) {
            throw new InvalidPageLimit($this->limit, self::MAX_LIMIT);
        }
    }

    public static function default(): self
    {
        return new self(self::DEFAULT_PAGE, self::DEFAULT_LIMIT);
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->limit;
    }
}
