<?php

declare(strict_types=1);

namespace Arc\TimeEntries\Tests;

use Arc\TimeEntries\Entries;
use Arc\TimeEntries\NewEntry;
use PHPUnit\Framework\TestCase;

final class EntriesTest extends TestCase
{
    public function test_create_is_idempotent_by_request_id(): void
    {
        $entries = new Entries();
        $dto = new NewEntry(task: 'Deep work', minutes: 90);

        $first = $entries->create($dto, 'req-abc-123');
        $second = $entries->create($dto, 'req-abc-123');

        $this->assertSame($first->id, $second->id);
    }

    public function test_dto_rejects_invalid_payload(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new NewEntry(task: '', minutes: 0);
    }
}
