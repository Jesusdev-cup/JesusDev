<?php

declare(strict_types=1);

namespace Arc\TimeEntries;

use Arc\TimeEntries\Contracts\EntryStore;
use Arc\TimeEntries\Events\EntryCreated;
use DateTimeImmutable;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Reference in-memory implementation of the contract.
 * Mutations dispatch events — the extension seam of the package.
 */
final class Entries implements EntryStore
{
    /** @var array<string, Entry> */
    private array $rows = [];

    /** @var array<string, Entry> */
    private array $seen = [];

    public function __construct(
        private readonly ?EventDispatcherInterface $events = null,
    ) {}

    /** @return Entry[] */
    public function listForDay(string $day): array
    {
        return array_values(array_filter(
            $this->rows,
            fn (Entry $e) => str_starts_with($e->createdAt->format('Y-m-d'), $day),
        ));
    }

    public function create(NewEntry $e, string $requestId): Entry
    {
        if (isset($this->seen[$requestId])) {
            return $this->seen[$requestId];
        }

        $entry = new Entry(
            id: bin2hex(random_bytes(16)),
            task: $e->task,
            minutes: $e->minutes,
            createdAt: new DateTimeImmutable(),
        );

        $this->rows[$entry->id] = $entry;
        $this->seen[$requestId] = $entry;

        $this->events?->dispatch(new EntryCreated($entry));

        return $entry;
    }
}
