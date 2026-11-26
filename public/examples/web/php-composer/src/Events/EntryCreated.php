<?php

declare(strict_types=1);

namespace Arc\TimeEntries\Events;

use Arc\TimeEntries\Entry;

/**
 * PSR-14 event — the package extends by dispatching events:
 * hooks with another name.
 */
final class EntryCreated
{
    public function __construct(public readonly Entry $entry) {}
}
