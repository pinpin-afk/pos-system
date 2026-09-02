<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;

class TwoFactorController extends Controller
{
    public function challenge(): Response
    {
        return Inertia::render('Auth/TwoFactorChallenge');
    }

    public function enable(Request $request, EnableTwoFactorAuthentication $enable): RedirectResponse
    {
        $enable($request->user());

        return back()->with('success', 'Scan QR, lalu masukkan kode OTP untuk konfirmasi.');
    }

    public function confirm(Request $request, ConfirmTwoFactorAuthentication $confirm): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $confirm($request->user(), $request->string('code')->toString());

        return back()->with('success', 'Autentikasi dua faktor aktif.');
    }

    public function disable(Request $request, DisableTwoFactorAuthentication $disable): RedirectResponse
    {
        $disable($request->user());

        return back()->with('success', 'Autentikasi dua faktor dimatikan.');
    }
}
