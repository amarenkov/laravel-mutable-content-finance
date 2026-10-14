<?php

namespace Amarenkov\MutableContentFinance\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * An operation was created.
 */
final readonly class OperationCreated implements ShouldDispatchAfterCommit
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
