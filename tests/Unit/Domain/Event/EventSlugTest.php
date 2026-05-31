<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Event;

use Domain\Event\Exception\InvalidEventSlug;
use Domain\Event\ValueObject\EventSlug;
use PHPUnit\Framework\TestCase;

final class EventSlugTest extends TestCase
{
    public function testCreateWithValidSlug(): void
    {
        $slug = EventSlug::create('my-event-2026');

        self::assertSame('my-event-2026', (string) $slug);
    }

    public function testCreateWithSingleWordSlug(): void
    {
        $slug = EventSlug::create('concert');

        self::assertSame('concert', (string) $slug);
    }

    public function testCreateNormalizesToLowercase(): void
    {
        $slug = EventSlug::create('My-Event');

        self::assertSame('my-event', (string) $slug);
    }

    public function testCreateTrimsWhitespace(): void
    {
        $slug = EventSlug::create('  my-event  ');

        self::assertSame('my-event', (string) $slug);
    }

    public function testCreateWithSpacesThrows(): void
    {
        $this->expectException(InvalidEventSlug::class);

        EventSlug::create('my event');
    }

    public function testCreateWithLeadingHyphenThrows(): void
    {
        $this->expectException(InvalidEventSlug::class);

        EventSlug::create('-my-event');
    }

    public function testCreateWithTrailingHyphenThrows(): void
    {
        $this->expectException(InvalidEventSlug::class);

        EventSlug::create('my-event-');
    }

    public function testCreateWithConsecutiveHyphensThrows(): void
    {
        $this->expectException(InvalidEventSlug::class);

        EventSlug::create('my--event');
    }

    public function testCreateWithSpecialCharactersThrows(): void
    {
        $this->expectException(InvalidEventSlug::class);

        EventSlug::create('my_event!');
    }

    public function testCreateWithEmptyStringThrows(): void
    {
        $this->expectException(InvalidEventSlug::class);

        EventSlug::create('');
    }

    public function testEqualsReturnsTrueForSameSlug(): void
    {
        $a = EventSlug::create('my-event');
        $b = EventSlug::create('MY-EVENT');

        self::assertTrue($a->equals($b));
    }

    public function testEqualsReturnsFalseForDifferentSlugs(): void
    {
        $a = EventSlug::create('event-a');
        $b = EventSlug::create('event-b');

        self::assertFalse($a->equals($b));
    }

    public function testToStringReturnsNormalizedValue(): void
    {
        $slug = EventSlug::create('My-Event-2026');

        self::assertSame('my-event-2026', (string) $slug);
    }
}
