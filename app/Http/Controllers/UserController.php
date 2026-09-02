<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Users/Index', [
            'users' => User::query()->orderBy('name')->paginate(15),
            'roles' => array_map(fn (UserRole $role) => [
                'value' => $role->value,
                'label' => $role->label(),
            ], UserRole::cases()),
            'creating' => $request->boolean('create'),
            'editingUser' => $request->filled('edit')
                ? User::query()->find($request->integer('edit'))
                : null,
            'branches' => Branch::query()->active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('users.index', ['create' => 1]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if (! filled($data['pin'] ?? null)) {
            unset($data['pin']);
        }

        $data['tenant_id'] = $request->user()->tenant_id ?? Tenant::query()->value('id');

        User::query()->create($data);

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(User $user): RedirectResponse
    {
        return redirect()->route('users.index', ['edit' => $user->id]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->safe()->except(['password', 'pin']);

        if (filled($request->validated('password'))) {
            $data['password'] = $request->validated('password');
        }

        if (filled($request->validated('pin'))) {
            $data['pin'] = $request->validated('pin');
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Tidak bisa menghapus akun sendiri.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Pengguna dihapus.');
    }
}
