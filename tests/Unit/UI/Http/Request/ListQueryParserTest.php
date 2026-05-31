<?php

declare(strict_types=1);

namespace Tests\Unit\UI\Http\Request;

use Domain\Common\ValueObject\PaginationCriteria;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use UI\Http\Request\ListQueryParser;

final class ListQueryParserTest extends TestCase
{
    // ─── pagination ───────────────────────────────────────────────────────────

    public function testPaginationDefaultsWhenParamsMissing(): void
    {
        ['page' => $page, 'limit' => $limit] = ListQueryParser::pagination(Request::create('/'));

        self::assertSame(PaginationCriteria::DEFAULT_PAGE, $page);
        self::assertSame(PaginationCriteria::DEFAULT_LIMIT, $limit);
    }

    public function testPaginationReadsExplicitValues(): void
    {
        $request = Request::create('/', 'GET', ['page' => 3, 'limit' => 50]);
        ['page' => $page, 'limit' => $limit] = ListQueryParser::pagination($request);

        self::assertSame(3, $page);
        self::assertSame(50, $limit);
    }

    public function testPaginationClampsPageToMinimumOne(): void
    {
        $request = Request::create('/', 'GET', ['page' => 0]);
        ['page' => $page] = ListQueryParser::pagination($request);

        self::assertSame(1, $page);
    }

    public function testPaginationClampsNegativePageToOne(): void
    {
        $request = Request::create('/', 'GET', ['page' => -5]);
        ['page' => $page] = ListQueryParser::pagination($request);

        self::assertSame(1, $page);
    }

    public function testPaginationClampsLimitToMaximum(): void
    {
        $request = Request::create('/', 'GET', ['limit' => 9999]);
        ['limit' => $limit] = ListQueryParser::pagination($request);

        self::assertSame(PaginationCriteria::MAX_LIMIT, $limit);
    }

    public function testPaginationClampsLimitToMinimumOne(): void
    {
        $request = Request::create('/', 'GET', ['limit' => 0]);
        ['limit' => $limit] = ListQueryParser::pagination($request);

        self::assertSame(1, $limit);
    }

    // ─── sort ─────────────────────────────────────────────────────────────────

    public function testSortDefaultsWhenParamsMissing(): void
    {
        ['sort' => $sort, 'direction' => $direction] = ListQueryParser::sort(Request::create('/'), ['name', 'city'], 'name');

        self::assertSame('name', $sort);
        self::assertSame('ASC', $direction);
    }

    public function testSortReadsExplicitField(): void
    {
        $request = Request::create('/', 'GET', ['sort' => 'city', 'direction' => 'DESC']);
        ['sort' => $sort, 'direction' => $direction] = ListQueryParser::sort($request, ['name', 'city'], 'name');

        self::assertSame('city', $sort);
        self::assertSame('DESC', $direction);
    }

    public function testSortFallsBackToDefaultForUnknownField(): void
    {
        $request = Request::create('/', 'GET', ['sort' => 'nonexistent']);
        ['sort' => $sort] = ListQueryParser::sort($request, ['name', 'city'], 'name');

        self::assertSame('name', $sort);
    }

    public function testSortDirectionNormalisedToUppercase(): void
    {
        $request = Request::create('/', 'GET', ['direction' => 'desc']);
        ['direction' => $direction] = ListQueryParser::sort($request, ['name'], 'name');

        self::assertSame('DESC', $direction);
    }

    public function testSortUsesCustomDefaultDirection(): void
    {
        ['direction' => $direction] = ListQueryParser::sort(
            Request::create('/'),
            ['placedAt'],
            'placedAt',
            'DESC',
        );

        self::assertSame('DESC', $direction);
    }
}
