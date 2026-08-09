<?php

declare(strict_types=1);

namespace UI\Http\Exception;

class UnauthenticatedRequest extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('User is not authenticated.');
    }
}
