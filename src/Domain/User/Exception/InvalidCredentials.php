<?php

declare(strict_types=1);

namespace Domain\User\Exception;

final class InvalidCredentials extends \DomainException
{
    public static function create(): self
    {
        return new self('Invalid credentials.');
    }
}
