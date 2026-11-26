<?php

namespace App\Domain\TimeEntries;

use Illuminate\Database\Eloquent\Model;

class TimeEntry extends Model
{
    /** request_id = the idempotency dedup key, unique per mutation. */
    protected $fillable = ['task', 'minutes', 'request_id'];

    protected function casts(): array
    {
        return ['minutes' => 'integer'];
    }
}
