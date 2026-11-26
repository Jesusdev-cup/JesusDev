<?php

declare(strict_types=1);

namespace Arc\TimeEntries\Contracts;

use Arc\TimeEntries\Entry;
use Arc\TimeEntries\NewEntry;

/**
 * The package's public API: consumers depend on this interface,
 * not on the implementation — same mold as WordPress contracts.
 */
interface EntryStore
{
    /** @return Entry[] */
    public function listForDay(string $day): array;

    /**
     * Idempotent create: a retry with the same $requestId returns
     * the stored entry instead of a duplicate.
     */
    public function create(NewEntry $e, string $requestId): Entry;
}
