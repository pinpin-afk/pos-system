<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

trait ScopedToBranch
{
    #[Scope]
    protected function forBranch(Builder $query, ?int $branchId): Builder
    {
        if ($branchId === null) {
            return $query->whereNull('branch_id');
        }

        return $query->where('branch_id', $branchId);
    }
}
