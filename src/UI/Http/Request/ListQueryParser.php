<?php

declare(strict_types=1);

namespace App\UI\Http\Request;

use App\Domain\Common\ValueObject\PaginationCriteria;
use App\Domain\Common\ValueObject\SortCriteria;
use App\Domain\Common\ValueObject\SortDirection;
use Symfony\Component\HttpFoundation\Request;

final class ListQueryParser
{
    public static function pagination(Request $request): PaginationCriteria
    {
        $page  = max(1, (int) $request->query->get('page', PaginationCriteria::DEFAULT_PAGE));
        $limit = (int) $request->query->get('limit', PaginationCriteria::DEFAULT_LIMIT);
        $limit = max(1, min($limit, PaginationCriteria::MAX_LIMIT));

        return new PaginationCriteria($page, $limit);
    }

    /**
     * @param string[] $allowedFields
     */
    public static function sort(
        Request $request,
        array   $allowedFields,
        string  $defaultField,
        SortDirection $defaultDirection = SortDirection::ASC,
    ): SortCriteria {
        $field = $request->query->getString('sort', $defaultField);
        if (!in_array($field, $allowedFields, true)) {
            $field = $defaultField;
        }

        $direction = SortDirection::fromString(
            $request->query->getString('direction', $defaultDirection->value),
        );

        return new SortCriteria($field, $direction);
    }
}
