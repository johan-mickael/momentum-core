<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\User;

use App\Domain\User\ValueObject\PlainPassword;
use PHPUnit\Framework\TestCase;

final class PlainPasswordTest extends TestCase
{
    public function testConstructWithValidPassword(): void
    {
        $password = new PlainPassword('securepassword');

        self::assertSame('securepassword', $password->toString());
    }

    public function testConstructWithExactlyEightCharactersSucceeds(): void
    {
        $password = new PlainPassword('12345678');

        self::assertSame('12345678', $password->toString());
    }

    public function testConstructWithTooShortPasswordThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Password must be at least 8 characters.');

        new PlainPassword('short');
    }

    public function testConstructWithSevenCharactersThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new PlainPassword('1234567');
    }

    public function testPasswordHasNoToStringMethod(): void
    {
        self::assertFalse(
            method_exists(PlainPassword::class, '__toString'),
            'PlainPassword must not expose __toString to prevent accidental logging.',
        );
    }
}
