<?php

namespace Tests\Feature\Controllers\Auth;

use App\Models\User;
use App\Notifications\TwoFactorCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LoginControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    public function test_a_user_with_an_account_that_is_locked_cannot_sign_in()
    {
        $user = User::factory()->create(['locked_until' => now()->addMinutes(5)]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password'
        ])->assertSessionHasErrors([
            'error' => 'This account has been temporarily locked. Please try again at a later moment.',
        ]);

        $this->assertGuest();
    }

    public function test_a_user_with_an_account_that_is_not_locked_can_sign_in()
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password'
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    }
    
    public function test_when_a_user_without_two_factor_signs_in_no_two_factor_code_will_be_generated()
    { 
        $this->post('/login', [
            'email' => $this->employee->email,
            'password' => 'password'
        ])->assertRedirect();

        $this->assertNull($this->employee->fresh()->two_factor_code);
    }

    public function test_when_a_user_with_two_factor_signs_in_a_two_factor_code_will_be_generated()
    {
        $this->post('/login', [
            'email' => $this->admin->email,
            'password' => 'password'
        ])->assertRedirect();

        $admin = $this->admin->fresh();

        $this->assertNotNull($admin->two_factor_code);
        $this->assertTrue(preg_match('/^\d{6}$/', $admin->two_factor_code) === 1);
        $this->assertEqualsWithDelta($admin->two_factor_expires_at, now()->addMinutes(5), 1);
    }

    public function test_the_user_will_not_receive_a_notification_if_they_sign_in_without_two_factor_enabled()
    {
        $this->post('/login', [
            'email' => $this->employee->email,
            'password' => 'password'
        ])->assertRedirect();

        Notification::assertNothingSentTo($this->employee);
    }

    public function test_the_user_will_receive_a_notification_if_they_sign_in_with_two_factor_enabled()
    {
        $this->post('/login', [
            'email' => $this->admin->email,
            'password' => 'password'
        ])->assertRedirect();

        Notification::assertSentTo($this->admin, TwoFactorCodeNotification::class);
    }

    public function test_when_a_user_with_two_factor_enabled_signs_out_the_two_factor_code_gets_erased()
    {
        $this->actingAs($this->admin);
        $this->admin->generateTwoFactorCode();

        $this->post(route('logout'));

        $this->assertNull($this->admin->fresh()->two_factor_code);
        $this->assertNull($this->admin->fresh()->two_factor_expires_at);
        $this->assertGuest();
    }
}
