<?php

declare(strict_types=1);

namespace Domain\Common\Id;

interface IdGeneratorInterface
{
    public function generate(): string;
}
