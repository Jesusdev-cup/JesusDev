<?php

namespace App\Domain\TimeEntries;

use App\Models\User;

/**
 * Policy = the single permission matrix for this domain.
 * $this->user()->can('time.write') in the Form Request reads this.
 */
class TimeEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('time.read');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('time.write');
    }
}
