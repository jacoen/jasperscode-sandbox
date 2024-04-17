<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Notifications\TwoFactorCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TwoFactorControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    public function test_a_guest_cannot_reach_the_two_factor_verification_page()
    {
        $this->get(route('verify.create'))
            ->assertRedirect('login');
    }

    public function test_a_user_with_two_factor_enabled_can_see_their_masked_email_address_on_the_two_factor_verification_form()
    {
        $this->verifiedWithTwoFactor();
        $maskedEmail = maskEmail($this->admin->email);

        $this->get(route('verify.create'))
            ->assertSeeText($maskedEmail);
    }

    public function test_a_guest_cannot_submit_a_two_factor_code()
    {
        $this->post(route('verify.store'), [
            'two_factor_code' => '123456',
        ])->assertRedirect(route('login'));
    }


    public function test_the_two_factor_code_is_required()
    {
        $this->verifiedWithTwoFactor();

        $this->post(route('verify.store'), [
            'two_factor_code' => ''
        ])->assertSessionHasErrors([
            'two_factor_code' => 'The two factor code field is required.',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->admin->id,
            'two_factor_code' => $this->admin->two_factor_code,
        ]);
    }

    public function test_the_two_factor_code_can_only_consist_of_numbers()
    {
        $this->verifiedWithTwoFactor();

        $this->post(route('verify.store'), [
            'two_factor_code' => 'ab*d_f'
        ])->assertSessionHasErrors([
            'two_factor_code' => 'The two factor code field must be a number.',
            'two_factor_code' => 'The two factor code field must be an integer.'
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->admin->id,
            'two_factor_code' => $this->admin->two_factor_code,
        ]);
    }

    public function test_the_two_factor_code_must_be_exactly_six_numbers()
    {
        $this->verifiedWithTwoFactor();

        $this->post(route('verify.store'), [
            'two_factor_code' => '12345'
        ])->assertSessionHasErrors([
            'two_factor_code' => 'The two factor code field must be 6 digits.',
        ]);

        $this->actingAs($this->admin)->post(route('verify.store'), [
            'two_factor_code' => '1234567'
        ])->assertSessionHasErrors([
            'two_factor_code' => 'The two factor code field must be 6 digits.',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->admin->id,
            'two_factor_code' => $this->admin->two_factor_code,
        ]);
    }

    public function test_the_two_factor_code_must_match_the_two_factor_code_of_the_current_user()
    {
        $user = User::factory()->create([
            'two_factor_enabled' => true,
            'two_factor_code' => '426897',
            'two_factor_expires_at' => now()->addMinutes(2),
        ]);

        $this->actingAs($user)->post(route('verify.store'), [
            'two_factor_code' => '578213',
        ])->assertSessionHasErrors([
            'two_factor_code' => 'The two factor code you have entered does not match.',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'two_factor_code' => $user->two_factor_code,
        ]);
    }

    public function test_the_attempt_counter_will_increase_when_the_user_enters_an_incorrect_two_factor_code()
    {
        $user = User::factory()->create([
            'two_factor_enabled' => true,
            'two_factor_code' => '426897',
            'two_factor_expires_at' => now()->addMinutes(2),
        ]);

        $this->actingAs($user)->post(route('verify.store'), [
            'two_factor_code' => '123456',
        ])->assertSessionHasErrors();

        $this->assertTrue(session()->has('two_factor_attempts'));
        $this->assertEquals(session('two_factor_attempts'), 1);
    }

    public function test_the_timestamp_of_the_last_two_factor_code_input_will_be_remembered_when_the_user_provides_an_incorrect_two_factor_code()
    {
        $user = User::factory()->create([
            'two_factor_enabled' => true,
            'two_factor_code' => '426897',
            'two_factor_expires_at' => now()->addMinutes(2),
        ]);

        $this->actingAs($user)->post(route('verify.store'), [
            'two_factor_code' => '123456',
        ])->assertSessionHasErrors();

        $this->assertTrue(session()->has('last_attempt_time'));
        $this->assertEquals(session('last_attempt_time'), now()->timestamp);
    }

    public function test_the_account_of_the_user_gets_locked_when_the_maximum_attempt_limit_has_been_exceeded()
    {
        $user = User::factory()->create([
            'two_factor_enabled' => true,
            'two_factor_code' => '426897',
            'two_factor_expires_at' => now()->addMinutes(5),
        ]);

        session(['two_factor_attempts' => 3]);

        $this->actingAs($user)->post(route('verify.store'), [
            'two_factor_code' => '123456',
        ])->assertSessionHasErrors();

        $this->assertEqualsWithDelta($user->locked_until, now()->addMinutes(10), 1);
    }

    public function test_the_two_factor_code_gets_erased_after_the_user_account_has_been_locked()
    {
        $user = User::factory()->create([
            'two_factor_enabled' => true,
            'two_factor_code' => '426897',
            'two_factor_expires_at' => now()->addMinutes(5),
        ]);

        session(['two_factor_attempts' => 3]);

        $this->actingAs($user)->post(route('verify.store'), [
            'two_factor_code' => '123456',
        ])->assertSessionHasErrors();

        $this->assertNull($user->two_factor_code);
        $this->assertNull($user->two_factor_expires_at);
    }

    public function test_the_user_gets_signed_out_after_the_account_has_been_locked()
    {
        $user = User::factory()->create([
            'two_factor_enabled' => true,
            'two_factor_code' => '426897',
            'two_factor_expires_at' => now()->addMinutes(5),
        ]);

        session(['two_factor_attempts' => 3]);

        $this->actingAs($user)->post(route('verify.store'), [
            'two_factor_code' => '123456',
        ])->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_a_message_will_be_displayed_when_the_user_has_exceeded_the_maximum_attempt_limit_for_two_factor_authentication()
    {
        $user = User::factory()->create([
            'two_factor_enabled' => true,
            'two_factor_code' => '426897',
            'two_factor_expires_at' => now()->addMinutes(5),
        ]);

        session(['two_factor_attempts' => 3]);

        $this->actingAs($user)->post(route('verify.store'), [
            'two_factor_code' => '123456',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'error' => 'Too many failed attempts. This account will now be locked for a period of 10 minutes.',
            ]);
    }

    public function test_the_two_factor_code_gets_erased_after_the_user_enters_their_code_correctly()
    {
        $this->actingAs($this->admin)->post(route('verify.store'), [
            'two_factor_code' => $this->admin->two_factor_code,
        ])->assertRedirect(route('home'));

        $this->assertNull($this->admin->fresh()->two_factor_code);
        $this->assertNull($this->admin->fresh()->two_factor_expires_at);

        $this->assertDatabaseHas('users', [
            'id' => $this->admin->id,
            'two_factor_code' => null,
        ]);
    }

    public function test_a_guest_cannot_resend_a_two_factor_code()
    {
        $this->get(route('verify.resend'))
            ->assertRedirect(route('login'));

        Notification::assertNothingSent();
    }

    public function test_a_user_without_two_factor_enabled_cannot_resend_a_two_factor_code()
    {
        $this->actingAs($this->employee)->get(route('verify.resend'))
            ->assertRedirect(route('home'));

        Notification::assertNothingSentTo($this->employee);

        $this->assertNull($this->employee->fresh()->two_factor_code);
    }

    public function test_a_user_without_two_factor_code_cannot_resend_a_new_two_factor_code()
    {
        $this->actingAs($this->admin)->get(route('verify.resend'))
            ->assertRedirect(route('home'));

        Notification::assertNotSentTo($this->admin, TwoFactorCodeNotification::class);

        $this->assertNull($this->admin->fresh()->two_factor_code);
    }

    public function test_a_user_with_an_expired_two_factor_code_cannot_resend_a_new_two_factor_code()
    {
        $user = User::factory()->create();
        $user->generateTwoFactorCode();

        $twoFactorCode = $user->two_factor_code;

        $this->actingAs($user)->get(route('verify.resend'))
            ->assertRedirect(route('home'));

        Notification::assertNotSentTo($user, TwoFactorCodeNotification::class);

        $this->assertEquals($twoFactorCode, $user->fresh()->two_factor_code);
    }

    public function test_a_user_with_two_factor_enabled_can_resend_a_new_two_factor_code()
    {
        $this->verifiedWithTwoFactor();

        $twoFactorCode = $this->admin->two_factor_code;

        $this->get(route('verify.resend'))
            ->assertRedirect(route('verify.create'))
            ->assertSessionHas('success', 'A new code has been sent to your email.');

        Notification::assertSentTo($this->admin, TwoFactorCodeNotification::class);

        $this->assertNotEquals($this->admin->fresh()->two_factor_code, $twoFactorCode);
    }

    protected function verifiedWithTwoFactor()
    {
        $this->be($this->admin);

        $this->admin->generateTwoFactorCode();
    }
}
