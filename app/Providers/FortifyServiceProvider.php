<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Responses\LoginResponse;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::loginView(fn () => Inertia::render('Auth/Login'));
        Fortify::requestPasswordResetLinkView(fn () => Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]));
        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('Auth/ResetPassword', [
            'email' => $request->string('email')->toString(),
            'token' => $request->route('token'),
        ]));
        Fortify::twoFactorChallengeView(fn () => Inertia::render('Auth/TwoFactorChallenge'));

        Fortify::authenticateUsing(function (Request $request): ?User {
            $user = User::query()->where('email', $request->string('email')->toString())->first();

            if ($user === null || ! $user->is_active || ! Hash::check($request->string('password')->toString(), $user->password)) {
                return null;
            }

            if (! $user->canAccessAdmin()) {
                throw ValidationException::withMessages([
                    Fortify::username() => 'Akun kasir harus masuk lewat halaman kasir.',
                ]);
            }

            return $user;
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
