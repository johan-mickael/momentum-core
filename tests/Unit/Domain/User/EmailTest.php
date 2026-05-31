<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\User;

use Domain\User\Exception\InvalidEmail;
use Domain\User\ValueObject\Email;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    public function testCreateWithValidEmail(): void
    {
        $email = Email::create('user@example.com');

        self::assertSame('user@example.com', (string) $email);
    }

    public function testCreateNormalizesEmailToLowercase(): void
    {
        $email = Email::create('User@Example.COM');

        self::assertSame('user@example.com', (string) $email);
    }

    public function testCreateTrimsWhitespace(): void
    {
        $email = Email::create('  user@example.com  ');

        self::assertSame('user@example.com', (string) $email);
    }

    public function testCreateWithInvalidEmailThrows(): void
    {
        $this->expectException(InvalidEmail::class);

        Email::create('not-an-email');
    }

    public function testCreateWithEmptyStringThrows(): void
    {
        $this->expectException(InvalidEmail::class);

        Email::create('');
    }

    public function testEqualsReturnsTrueForSameEmail(): void
    {
        $a = Email::create('user@example.com');
        $b = Email::create('USER@EXAMPLE.COM');

        self::assertTrue($a->equals($b));
    }

    public function testEqualsReturnsFalseForDifferentEmail(): void
    {
        $a = Email::create('a@example.com');
        $b = Email::create('b@example.com');

        self::assertFalse($a->equals($b));
    }

    public function testToStringReturnsNormalizedEmail(): void
    {
        $email = Email::create('Test@Example.Com');

        self::assertSame('test@example.com', (string) $email);
    }
}
