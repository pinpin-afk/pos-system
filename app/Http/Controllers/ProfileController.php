<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfilePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(): Response
    {
        $user = request()->user();

        return Inertia::render('Profile/Edit', [
            'twoFactorEnabled' => $user?->hasEnabledTwoFactorAuthentication() ?? false,
            'twoFactorPending' => filled($user?->two_factor_secret) && blank($user?->two_factor_confirmed_at),
            'qrSvg' => filled($user?->two_factor_secret) ? $user->twoFactorQrCodeSvg() : null,
            'recoveryCodes' => filled($user?->two_factor_secret) ? $user->recoveryCodes() : [],
        ]);
    }

    public function updatePassword(UpdateProfilePasswordRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => Hash::make($request->validated('password')),
        ]);

        return back()->with('success', 'Password berhasil diganti.');
    }
}
