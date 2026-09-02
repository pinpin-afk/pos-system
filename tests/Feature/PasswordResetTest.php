<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_screen_can_be_rendered(): void
    {
        $this->get('/forgot-password')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Auth/ForgotPassword'));
    }

    public function test_reset_link_can_be_requested(): void
    {
        Notification::fake();
        $this->seedCore();
        $owner = User::factory()->owner()->create();

        $this->post('/forgot-password', ['email' => $owner->email])->assertSessionHas('status');

        Notification::assertSentTo($owner, ResetPassword::class);
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();
        $this->seedCore();
        $owner = User::factory()->owner()->create();

        $this->post('/forgot-password', ['email' => $owner->email]);

        Notification::assertSentTo($owner, ResetPassword::class, function (ResetPassword $notification) use ($owner) {
            $this->get('/reset-password/'.$notification->token)
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page->component('Auth/ResetPassword'));

            $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $owner->email,
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])->assertRedirect('/login');

            $this->assertTrue(Hash::check('new-password', $owner->fresh()->password));

            return true;
        });
    }

    public function test_user_can_change_password_from_profile(): void
    {
        $this->seedCore();
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)
            ->put(route('profile.password.update'), [
                'current_password' => 'password',
                'password' => 'changed-password',
                'password_confirmation' => 'changed-password',
            ])
            ->assertRedirect();

        $this->assertTrue(Hash::check('changed-password', $owner->fresh()->password));
    }
}
