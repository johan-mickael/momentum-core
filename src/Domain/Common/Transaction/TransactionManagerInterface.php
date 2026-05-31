<?php

declare(strict_types=1);

namespace Domain\Common\Transaction;

interface TransactionManagerInterface
{
    public function flush(): void;
}
