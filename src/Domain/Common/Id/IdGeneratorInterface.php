<?php

declare(strict_types=1);

namespace App\Domain\Common\Id;

interface IdGeneratorInterface
{
    public function generate(): string;
}
