<?php

declare(strict_types=1);

namespace Arc\TimeEntries;

use DateTimeImmutable;

/**
 * The domain resource — immutable readonly value object.
 */
final class Entry
{
    public function __construct(
        public readonly string $id,
        public readonly string $task,
        public readonly int $minutes,
        public readonly DateTimeImmutable $createdAt,
    ) {}
}
