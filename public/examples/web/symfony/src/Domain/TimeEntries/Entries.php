<?php

declare(strict_types=1);

namespace App\Domain\TimeEntries;

use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

/**
 * The domain service: mutations go through here.
 * firstOfRequestId = idempotency keyed on the request id.
 */
final class Entries
{
    /** @var array<string, TimeEntry> */
    private array $rows = [];

    /** @var array<string, TimeEntry> */
    private array $seen = [];

    /** @return TimeEntry[] */
    public function listForDay(string $day): array
    {
        return array_values(array_filter(
            $this->rows,
            fn (TimeEntry $e) => str_starts_with($e->createdAt->format('Y-m-d'), $day),
        ));
    }

    public function create(NewEntry $e, string $requestId): TimeEntry
    {
        if (isset($this->seen[$requestId])) {
            return $this->seen[$requestId]; // retry → same stored entry
        }

        $entry = new TimeEntry(
            id: Uuid::v4()->toRfc4122(),
            task: $e->task,
            minutes: $e->minutes,
            createdAt: new DateTimeImmutable(),
        );

        $this->rows[$entry->id] = $entry;
        $this->seen[$requestId] = $entry;

        return $entry;
    }
}
