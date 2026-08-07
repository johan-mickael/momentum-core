<?php

declare(strict_types=1);

namespace Domain\Common\Transaction;

interface TransactionManagerInterface
{
    public function flush(): void;

    /**
     * Runs $operation inside a single DB transaction, committing on success
     * and rolling back on any throwable. Required for aggregate-level
     * pessimistic locks (e.g. getByIdForUpdate), which need an open
     * transaction to acquire the row lock.
     *
     * @template T
     *
     * @param callable(): T $operation
     *
     * @return T
     */
    public function transactional(callable $operation): mixed;
}
