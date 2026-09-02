<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use App\Models\StoreSetting;
use App\Support\LocationContext;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'permissions' => $user->permissionValues(),
                    'can_access_admin' => $user->canAccessAdmin(),
                    'two_factor_enabled' => $user->hasEnabledTwoFactorAuthentication(),
                ] : null,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'created_customer' => $request->session()->get('created_customer'),
            ],
            'notifications' => fn () => $user
                ? $user->unreadNotifications()->latest()->limit(8)->get()->map(fn ($notification) => [
                    'id' => $notification->id,
                    'title' => $notification->data['title'] ?? 'Notifikasi',
                    'message' => $notification->data['message'] ?? '',
                    'created_at' => $notification->created_at?->diffForHumans(),
                ])
                : [],
            'location' => fn () => [
                'branch' => app(LocationContext::class)->branch($user),
                'branches' => $user?->canAccessAdmin()
                    ? Branch::query()->active()->orderBy('name')->get(['id', 'name', 'code'])
                    : [],
            ],
            'store' => fn () => StoreSetting::query()->first([
                'store_name',
                'allow_discount',
                'tax_rate',
                'tax_inclusive',
                'logo',
                'timezone',
                'currency',
            ]),
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
        ];
    }
}
