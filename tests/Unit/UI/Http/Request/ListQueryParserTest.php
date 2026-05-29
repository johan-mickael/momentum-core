<?php

declare(strict_types=1);

namespace App\Tests\Unit\UI\Http\Request;

use App\Domain\Common\ValueObject\PaginationCriteria;
use App\Domain\Common\ValueObject\SortDirection;
use App\UI\Http\Request\ListQueryParser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class ListQueryParserTest extends TestCase
{
    // ─── pagination ───────────────────────────────────────────────────────────

    public function testPaginationDefaultsWhenParamsMissing(): void
    {
        $criteria = ListQueryParser::pagination(Request::create('/'));

        self::assertSame(PaginationCriteria::DEFAULT_PAGE, $criteria->page);
        self::assertSame(PaginationCriteria::DEFAULT_LIMIT, $criteria->limit);
    }

    public function testPaginationReadsExplicitValues(): void
    {
        $request  = Request::create('/', 'GET', ['page' => 3, 'limit' => 50]);
        $criteria = ListQueryParser::pagination($request);

        self::assertSame(3, $criteria->page);
        self::assertSame(50, $criteria->limit);
    }

    public function testPaginationClampsPageToMinimumOne(): void
    {
        $request  = Request::create('/', 'GET', ['page' => 0]);
        $criteria = ListQueryParser::pagination($request);

        self::assertSame(1, $criteria->page);
    }

    public function testPaginationClampsNegativePageToOne(): void
    {
        $request  = Request::create('/', 'GET', ['page' => -5]);
        $criteria = ListQueryParser::pagination($request);

        self::assertSame(1, $criteria->page);
    }

    public function testPaginationClampsLimitToMaximum(): void
    {
        $request  = Request::create('/', 'GET', ['limit' => 9999]);
        $criteria = ListQueryParser::pagination($request);

        self::assertSame(PaginationCriteria::MAX_LIMIT, $criteria->limit);
    }

    public function testPaginationClampsLimitToMinimumOne(): void
    {
        $request  = Request::create('/', 'GET', ['limit' => 0]);
        $criteria = ListQueryParser::pagination($request);

        self::assertSame(1, $criteria->limit);
    }

    // ─── sort ─────────────────────────────────────────────────────────────────

    public function testSortDefaultsWhenParamsMissing(): void
    {
        $criteria = ListQueryParser::sort(Request::create('/'), ['name', 'city'], 'name');

        self::assertSame('name', $criteria->field);
        self::assertSame(SortDirection::ASC, $criteria->direction);
    }

    public function testSortReadsExplicitField(): void
    {
        $request  = Request::create('/', 'GET', ['sort' => 'city', 'direction' => 'DESC']);
        $criteria = ListQueryParser::sort($request, ['name', 'city'], 'name');

        self::assertSame('city', $criteria->field);
        self::assertSame(SortDirection::DESC, $criteria->direction);
    }

    public function testSortFallsBackToDefaultForUnknownField(): void
    {
        $request  = Request::create('/', 'GET', ['sort' => 'nonexistent']);
        $criteria = ListQueryParser::sort($request, ['name', 'city'], 'name');

        self::assertSame('name', $criteria->field);
    }

    public function testSortDirectionCaseInsensitive(): void
    {
        $request  = Request::create('/', 'GET', ['direction' => 'desc']);
        $criteria = ListQueryParser::sort($request, ['name'], 'name');

        self::assertSame(SortDirection::DESC, $criteria->direction);
    }

    public function testSortUsesCustomDefaultDirection(): void
    {
        $criteria = ListQueryParser::sort(
            Request::create('/'),
            ['placedAt'],
            'placedAt',
            SortDirection::DESC,
        );

        self::assertSame(SortDirection::DESC, $criteria->direction);
    }
}
