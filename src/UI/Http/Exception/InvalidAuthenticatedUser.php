<?php

declare(strict_types=1);

namespace UI\Http\Exception;

class InvalidAuthenticatedUser extends \LogicException
{
    public function __construct()
    {
        parent::__construct('Invalid authenticated user.');
    }
}
