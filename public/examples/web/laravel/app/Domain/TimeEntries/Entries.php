<?php

namespace App\Domain\TimeEntries;

use Illuminate\Database\Eloquent\Collection;

/**
 * The domain service: owns the mutation logic for time-entries.
 * Controllers delegate here — they never build queries inline.
 */
class Entries
{
    /** @return Collection<int, TimeEntry> */
    public function listForDay(string $day): Collection
    {
        return TimeEntry::query()->whereDate('created_at', $day)->get();
    }

    /**
     * Idempotent create: a retry with the same request id returns the
     * stored entry — firstOrCreate keyed on the dedup column.
     */
    public function create(array $data, string $requestId): TimeEntry
    {
        return TimeEntry::query()->firstOrCreate(
            ['request_id' => $requestId],
            [
                'task' => $data['task'],
                'minutes' => $data['minutes'],
            ],
        );
    }
}
