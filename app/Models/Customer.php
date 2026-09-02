<?php

namespace App\Models;

use App\Models\Concerns\ScopedToBranch;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['branch_id', 'name', 'phone', 'email', 'address', 'birthday', 'member_number', 'points', 'is_walk_in'])]
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    use ScopedToBranch;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birthday' => 'date',
            'points' => 'integer',
            'is_walk_in' => 'boolean',
            'branch_id' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return HasMany<Sale, $this>
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    #[Scope]
    protected function walkIn(Builder $query): Builder
    {
        return $query->where('is_walk_in', true);
    }

    public static function walkInFor(?int $branchId): ?self
    {
        return static::query()->walkIn()->forBranch($branchId)->first();
    }

    public static function ensureWalkIn(?int $branchId): self
    {
        return static::query()->firstOrCreate(
            [
                'branch_id' => $branchId,
                'is_walk_in' => true,
            ],
            [
                'name' => 'Walk-in Customer',
                'phone' => null,
                'email' => null,
                'address' => null,
                'points' => 0,
            ],
        );
    }
}
