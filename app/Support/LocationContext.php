<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\User;
use App\Models\Warehouse;

class LocationContext
{
    public function branch(?User $user = null): ?Branch
    {
        $user ??= auth()->user();

        if ($user?->canAccessAdmin()) {
            $sessionBranch = $this->branchFromSession();

            if ($sessionBranch !== null) {
                return $sessionBranch;
            }
        }

        if ($user?->branch_id) {
            $branch = Branch::query()->find($user->branch_id);

            if ($branch !== null) {
                return $branch;
            }
        }

        return $this->branchFromSession()
            ?? Branch::query()->orderBy('id')->first();
    }

    private function branchFromSession(): ?Branch
    {
        $sessionId = session('current_branch_id');

        if (! $sessionId) {
            return null;
        }

        return Branch::query()->find($sessionId);
    }

    public function warehouse(?User $user = null): ?Warehouse
    {
        $branch = $this->branch($user);

        return $branch?->defaultWarehouse()
            ?? Warehouse::query()->orderBy('id')->first();
    }

    /**
     * @return list<int>
     */
    public function warehouseIds(?User $user = null): array
    {
        $branch = $this->branch($user);

        if ($branch === null) {
            return [];
        }

        return $branch->warehouses()->orderBy('id')->pluck('id')->all();
    }
}
