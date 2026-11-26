<?php

namespace App\Domain\TimeEntries;

use DateTimeImmutable;

/**
 * Domain entity — plain object, no framework coupling beyond the type.
 */
final class TimeEntry
{
    public function __construct(
        public readonly string $id,
        public readonly string $task,
        public readonly int $minutes,
        public readonly DateTimeImmutable $createdAt,
    ) {}
}
