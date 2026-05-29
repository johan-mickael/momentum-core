<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Common\ValueObject;

use App\Domain\Common\ValueObject\SortDirection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SortDirectionTest extends TestCase
{
    #[DataProvider('provideValidAscValues')]
    public function testAscVariantsResolveToAsc(string $input): void
    {
        self::assertSame(SortDirection::ASC, SortDirection::fromString($input));
    }

    public static function provideValidAscValues(): array
    {
        return [
            'uppercase ASC' => ['ASC'],
            'lowercase asc' => ['asc'],
            'mixed Asc'     => ['Asc'],
        ];
    }

    #[DataProvider('provideValidDescValues')]
    public function testDescVariantsResolveToDesc(string $input): void
    {
        self::assertSame(SortDirection::DESC, SortDirection::fromString($input));
    }

    public static function provideValidDescValues(): array
    {
        return [
            'uppercase DESC' => ['DESC'],
            'lowercase desc' => ['desc'],
            'mixed Desc'     => ['Desc'],
        ];
    }

    #[DataProvider('provideInvalidValues')]
    public function testInvalidValueFallsBackToAsc(string $input): void
    {
        self::assertSame(SortDirection::ASC, SortDirection::fromString($input));
    }

    public static function provideInvalidValues(): array
    {
        return [
            'empty string'    => [''],
            'random string'   => ['random'],
            'numeric string'  => ['1'],
        ];
    }
}
