<?php

namespace Amarenkov\MutableContentFinance\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * An operation was deleted.
 */
final readonly class OperationDeleted implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    // public
    public function __construct(
        public int $operationId,
        public ?int $userId,
    )
    {
    }
}
