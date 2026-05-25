<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\User;

use App\Domain\User\Exception\InvalidPassword;
use App\Domain\User\ValueObject\PlainPassword;
use PHPUnit\Framework\TestCase;

final class PlainPasswordTest extends TestCase
{
    public function testConstructWithValidPassword(): void
    {
        $password = new PlainPassword('Secure1password');

        self::assertSame('Secure1password', $password->toString());
    }

    public function testConstructWithExactlyEightCharactersSucceeds(): void
    {
        $password = new PlainPassword('Secure1!');

        self::assertSame('Secure1!', $password->toString());
    }

    public function testConstructWithTooShortPasswordThrows(): void
    {
        $this->expectException(InvalidPassword::class);
        $this->expectExceptionMessage('Password must be at least 8 characters.');

        new PlainPassword('Sh0rt!');
    }

    public function testConstructWithSevenCharactersThrows(): void
    {
        $this->expectException(InvalidPassword::class);

        new PlainPassword('Short1!');
    }

    public function testConstructWithoutUppercaseThrows(): void
    {
        $this->expectException(InvalidPassword::class);
        $this->expectExceptionMessage('Password must contain at least one uppercase letter.');

        new PlainPassword('nouppercase1');
    }

    public function testConstructWithoutDigitThrows(): void
    {
        $this->expectException(InvalidPassword::class);
        $this->expectExceptionMessage('Password must contain at least one digit.');

        new PlainPassword('NoDigitHere!');
    }

    public function testPasswordHasNoToStringMethod(): void
    {
        self::assertFalse(
            method_exists(PlainPassword::class, '__toString'),
            'PlainPassword must not expose __toString to prevent accidental logging.',
        );
    }
}
