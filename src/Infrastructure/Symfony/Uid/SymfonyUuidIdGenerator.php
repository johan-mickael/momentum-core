<?php

declare(strict_types=1);

namespace Infrastructure\Symfony\Uid;

use Domain\Common\Id\IdGeneratorInterface;
use Symfony\Component\Uid\Uuid;

final class SymfonyUuidIdGenerator implements IdGeneratorInterface
{
    public function generate(): string
    {
        return Uuid::v7()->toRfc4122();
    }
}
