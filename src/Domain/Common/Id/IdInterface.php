<?php

declare(strict_types=1);

namespace Domain\Common\Id;

interface IdInterface
{
    public function __toString(): string;
}
