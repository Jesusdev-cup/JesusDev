<?php

declare(strict_types=1);

namespace Arc\TimeEntries;

use InvalidArgumentException;

/**
 * The schema of the contract — fully bounded at construction.
 * The domain can never hold an out-of-contract value.
 */
final class NewEntry
{
    public function __construct(
        public readonly string $task,
        public readonly int $minutes,
    ) {
        if (trim($task) === '') {
            throw new InvalidArgumentException('task is required');
        }
        if (strlen($task) > 255) {
            throw new InvalidArgumentException('task must be at most 255 chars');
        }
        if ($minutes < 1 || $minutes > 1440) {
            throw new InvalidArgumentException('minutes must be between 1 and 1440');
        }
    }
}
