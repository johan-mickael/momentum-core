<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\User;

use Domain\User\Exception\InvalidUserId;
use Domain\User\ValueObject\UserId;
use PHPUnit\Framework\TestCase;

final class UserIdTest extends TestCase
{
    private const string VALID_UUID = '123e4567-e89b-12d3-a456-426614174000';

    public function testFromStringWithValidUuid(): void
    {
        $id = UserId::fromString(self::VALID_UUID);

        self::assertSame(self::VALID_UUID, (string) $id);
    }

    public function testFromStringWithInvalidUuidThrows(): void
    {
        $this->expectException(InvalidUserId::class);

        UserId::fromString('not-a-uuid');
    }

    public function testFromStringWithEmptyStringThrows(): void
    {
        $this->expectException(InvalidUserId::class);

        UserId::fromString('');
    }

    public function testToStringReturnsOriginalValue(): void
    {
        $id = UserId::fromString(self::VALID_UUID);

        self::assertSame(self::VALID_UUID, (string) $id);
    }
}
