<?php

declare(strict_types=1);

namespace Arc\TimeEntries;

use InvalidArgumentException;

/**
 * The schema of the contract — validated at construction.
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
        if ($minutes < 1) {
            throw new InvalidArgumentException('minutes must be >= 1');
        }
    }
}
