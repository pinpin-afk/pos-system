<?php

namespace App\Models;

use App\Enums\Permission;
use App\Enums\ShiftStatus;
use App\Enums\UserRole;
use App\Support\RolePermissions;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['name', 'email', 'password', 'pin', 'card_number', 'role', 'is_active', 'tenant_id', 'branch_id'])]
#[Hidden(['password', 'remember_token', 'pin', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'pin' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    public function isOwner(): bool
    {
        return $this->role === UserRole::Owner;
    }

    public function isCashier(): bool
    {
        return $this->role === UserRole::Cashier;
    }

    public function canAccessAdmin(): bool
    {
        return $this->role?->canAccessAdmin() ?? false;
    }

    public function hasPermission(Permission|string $permission): bool
    {
        $needed = $permission instanceof Permission
            ? $permission
            : Permission::from($permission);

        return in_array($needed, RolePermissions::for($this->role), true);
    }

    /**
     * @return list<string>
     */
    public function permissionValues(): array
    {
        return array_map(
            fn (Permission $permission) => $permission->value,
            RolePermissions::for($this->role),
        );
    }

    /**
     * @return HasMany<CashierShift, $this>
     */
    public function shifts(): HasMany
    {
        return $this->hasMany(CashierShift::class);
    }

    /**
     * @return HasMany<Sale, $this>
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'cashier_id');
    }

    public function openShift(): ?CashierShift
    {
        return $this->shifts()->where('status', ShiftStatus::Open)->first();
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
