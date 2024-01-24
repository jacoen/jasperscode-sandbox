<?php

namespace Tests\Feature\Controllers\Auth;

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
    
    public function test_when_a_user_without_two_factor_signs_in_no_two_factor_code_will_be_generated()
    {
        $this->assertFalse($this->employee->two_factor_enabled); 
        
        $this->post('/login', [
            'email' => $this->employee->email,
            'password' => 'password'
        ])->assertRedirect();

        $this->assertNull($this->employee->fresh()->two_factor_code);
    }

    public function test_when_a_user_with_two_factor_signs_in_a_two_factor_code_will_be_generated()
    {
        $this->assertTrue($this->admin->two_factor_enabled);

        $this->post('/login', [
            'email' => $this->admin->email,
            'password' => 'password'
        ])->assertRedirect();

        $admin = $this->admin->fresh();

        $this->assertNotNull($admin->two_factor_code);
        $this->assertTrue(preg_match('/^\d{6}$/', $admin->two_factor_code) === 1);
        $this->assertEqualsWithDelta($admin->two_factor_expires_at, now()->addMinutes(5), 1);
    }

    public function test_no_notification_will_be_sent_to_the_user_if_they_do_not_have_two_factor_enabled()
    {
        $this->post('/login', [
            'email' => $this->employee->email,
            'password' => 'password'
        ])->assertRedirect();
    }

    public function test_when_a_user_with_two_factor_signs_in_a_notification_will_ben_sent_to_this_user()
    {
        $this->post('/login', [
            'email' => $this->employee->email,
            'password' => 'password'
        ])->assertRedirect();

        Notification::assertNothingSentTo($this->employee);
    }

    public function test_the_two_factor_code_will_be_reset_if_a_user_with_two_factor_enabled_signs_out()
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
