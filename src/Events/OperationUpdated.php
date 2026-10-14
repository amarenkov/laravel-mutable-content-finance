<?php

namespace Amarenkov\MutableContentFinance\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

use Amarenkov\MutableContent\Domain\Log\Changes;

/**
 * Fields or splits of an operation were changed.
 */
final readonly class OperationUpdated implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    // public
    public function __construct(
        public int $operationId,
        public ?int $userId,
        public Changes $changes,
        public bool $splitsChanged = false,
    )
    {
    }
}
