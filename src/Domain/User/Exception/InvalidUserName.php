<?php

declare(strict_types=1);

namespace App\Domain\User\Exception;

final class InvalidUserName extends \DomainException
{
    public static function firstName(): self
    {
        return new self('First name is invalid.');
    }

    public static function lastName(): self
    {
        return new self('Last name is invalid.');
    }
}
