<?php

declare(strict_types=1);

namespace App\UI\Http\Request;

use App\Domain\Common\ValueObject\PaginationCriteria;
use Symfony\Component\HttpFoundation\Request;

final class ListQueryParser
{
    /**
     * @return array{page: int, limit: int}
     */
    public static function pagination(Request $request): array
    {
        $page  = max(1, (int) $request->query->get('page', PaginationCriteria::DEFAULT_PAGE));
        $limit = (int) $request->query->get('limit', PaginationCriteria::DEFAULT_LIMIT);
        $limit = max(1, min($limit, PaginationCriteria::MAX_LIMIT));

        return ['page' => $page, 'limit' => $limit];
    }

    /**
     * @param string[] $allowedFields
     * @return array{sort: string, direction: string}
     */
    public static function sort(
        Request $request,
        array   $allowedFields,
        string  $defaultField,
        string  $defaultDirection = 'ASC',
    ): array {
        $sort = $request->query->getString('sort', $defaultField);
        if (!in_array($sort, $allowedFields, true)) {
            $sort = $defaultField;
        }

        $direction = strtoupper($request->query->getString('direction', $defaultDirection));
        if (!in_array($direction, ['ASC', 'DESC'], true)) {
            $direction = $defaultDirection;
        }

        return ['sort' => $sort, 'direction' => $direction];
    }
}
