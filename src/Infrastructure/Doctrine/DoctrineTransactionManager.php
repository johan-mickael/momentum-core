<?php

declare(strict_types=1);

namespace Infrastructure\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Domain\Common\Transaction\TransactionManagerInterface;

final class DoctrineTransactionManager implements TransactionManagerInterface
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function flush(): void
    {
        $this->em->flush();
    }
}
