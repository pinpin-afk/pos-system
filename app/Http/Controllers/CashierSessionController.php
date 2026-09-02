<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CashierSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/CashierLogin');
    }

    public function store(Request $request): RedirectResponse
    {
        $mode = $request->input('mode', 'email');

        $user = match ($mode) {
            'pin' => $this->userFromPin($request),
            'card' => $this->userFromCard($request),
            default => $this->userFromEmail($request),
        };

        if ($user === null || ! $user->is_active) {
            throw ValidationException::withMessages([
                $this->errorKey($mode) => 'Kredensial kasir tidak valid.',
            ]);
        }

        if (! $user->isCashier()) {
            throw ValidationException::withMessages([
                $this->errorKey($mode) => 'Akun admin harus masuk lewat halaman admin.',
            ]);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('pos.index'));
    }

    private function userFromEmail(Request $request): ?User
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->where('email', mb_strtolower($credentials['email']))
            ->first();

        if ($user === null || ! Hash::check($credentials['password'], $user->password)) {
            return null;
        }

        return $user;
    }

    private function userFromPin(Request $request): ?User
    {
        $credentials = $request->validate([
            'pin' => ['required', 'digits_between:4,6'],
        ]);

        $matches = User::query()
            ->where('role', 'cashier')
            ->whereNotNull('pin')
            ->get()
            ->filter(fn (User $user) => Hash::check($credentials['pin'], $user->pin));

        if ($matches->count() !== 1) {
            return null;
        }

        return $matches->first();
    }

    private function userFromCard(Request $request): ?User
    {
        $credentials = $request->validate([
            'card_number' => ['required', 'string', 'max:50'],
        ]);

        return User::query()
            ->where('card_number', $credentials['card_number'])
            ->first();
    }

    private function errorKey(string $mode): string
    {
        return match ($mode) {
            'pin' => 'pin',
            'card' => 'card_number',
            default => 'email',
        };
    }
}
