<?php

declare(strict_types=1);

namespace Domain\Venue\ValueObject;

use Domain\Venue\Exception\InvalidAddress;

final readonly class Address
{
    private function __construct(
        private string $street,
        private string $city,
        private string $zipCode,
        private string $country,
    ) {
        $this->assertValid();
    }

    public static function create(
        string $street,
        string $city,
        string $zipCode,
        string $country,
    ): self {
        return new self($street, $city, $zipCode, $country);
    }

    public function street(): string
    {
        return $this->street;
    }

    public function city(): string
    {
        return $this->city;
    }

    public function zipCode(): string
    {
        return $this->zipCode;
    }

    public function country(): string
    {
        return $this->country;
    }

    public function equals(self $other): bool
    {
        return
            $this->street === $other->street
            && $this->city === $other->city
            && $this->zipCode === $other->zipCode
            && $this->country === $other->country;
    }

    public function __toString(): string
    {
        return sprintf(
            '%s, %s %s, %s',
            $this->street,
            $this->zipCode,
            $this->city,
            $this->country
        );
    }

    private function assertValid(): void
    {
        if ('' === trim($this->street)) {
            throw new InvalidAddress('Street cannot be empty.');
        }

        if ('' === trim($this->city)) {
            throw new InvalidAddress('City cannot be empty.');
        }

        if ('' === trim($this->zipCode)) {
            throw new InvalidAddress('Zip code cannot be empty.');
        }

        if ('' === trim($this->country)) {
            throw new InvalidAddress('Country cannot be empty.');
        }
    }
}
