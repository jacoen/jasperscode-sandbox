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

    public function test_a_guest_cannot_visit_the_two_factor_verification_page()
    {
        $this->get(route('verify.create'))
            ->assertRedirect('login');
    }

    public function test_a_user_with_two_factor_authentication_enabled_and_a_two_factor_code_can_visit_the_two_factor_verification_page()
    {
        $user = User::factory()->withTwoFactorEnabled()->create([
            'email' => 'david.smith@example.com',
        ]);

        $this->actingAs($user)->get(route('verify.create'))
            ->assertOk();
    }

    public function test_the_masked_email_address_is_displayed_on_the_verification_page_when_a_user_with_two_factor_enabled_and_a_two_factor_code_visits_the_page()
    {
        $user = User::factory()->withTwoFactorEnabled()->create([
            'email' => 'david.smith@example.com',
        ]);

        $this->actingAs($user)->get(route('verify.create'))
            ->assertSeeText([
                'email' => 'd********@example.com',
            ]);
    }

    public function test_the_two_factor_code_is_required()
    {
        $user = User::factory()->withTwoFactorEnabled()->create();

        $this->actingAs($user)->post(route('verify.store'), [
            'two_factor_code' => '',
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
        $user = User::factory()->withTwoFactorEnabled()->create();

        $this->actingAs($user)->post(route('verify.store'), [
            'two_factor_code' => 'ab*d_f',
        ])->assertSessionHasErrors([
            'two_factor_code' => 'The two factor code field must be a number.',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->admin->id,
            'two_factor_code' => $this->admin->two_factor_code,
        ]);
    }

    public function test_the_two_factor_code_must_be_exactly_six_numbers()
    {
        $user = User::factory()->withTwoFactorEnabled()->create();
        $secondUser = User::factory()->withTwoFactorEnabled()->create();

        $this->actingAs($user)->post(route('verify.store'), [
            'two_factor_code' => '12345',
        ])->assertSessionHasErrors([
            'two_factor_code' => 'The two factor code field must be 6 digits.',
        ]);

        $this->actingAs($secondUser)->post(route('verify.store'), [
            'two_factor_code' => '1234567',
        ])->assertSessionHasErrors([
            'two_factor_code' => 'The two factor code field must be 6 digits.',
        ]);

        $this->assertNotNull($user->fresh()->two_factor_code);
        $this->assertNotNull($secondUser->fresh()->two_factor_code);
    }

    public function test_the_two_factor_attempt_counter_resets_after_five_minutes_since_the_last_attempt()
    {
        $user = User::factory()->withTwoFactorEnabled()->create([
            'created_at' => now()->subMinutes(10),
            'updated_at' => now()->subMinutes(10),
            'two_factor_attempts' => 3,
            'last_attempt_at' => now()->subMinutes(6), 
        ]);

        $this->actingAs($user)->post(route('verify.store'), [
            'two_factor_code' => '123456',
        ]);

        $this->assertEquals($user->fresh()->two_factor_attempts, 1);
    }

    public function test_user_will_receive_an_error_message_when_enter_an_invalid_two_factor_code()
    {
        $user = User::factory()->withTwoFactorEnabled()->create([
            'two_factor_code' => '197382',
        ]);

        $this->actingAs($user)->post(route('verify.store'), [
            'two_factor_code' => '123456',
        ])->assertSessionHasErrors([
            'two_factor_code' => 'The two factor code you have entered does not match.',
        ]);
    }

    public function test_the_two_factor_attempt_counter_increments_with_each_failed_attempt()
    {
        $user = User::factory()->withTwoFactorEnabled()->create([
            'created_at' => now()->subMinutes(10),
            'updated_at' => now()->subMinutes(10),
        ]);

        $this->actingAs($user)->post(route('verify.store'), [
            'two_factor_code' => '123456',
        ]);

        $this->assertNotNull($user->two_factor_code);
        $this->assertEquals($user->fresh()->two_factor_attempts, 1);
        $this->assertEqualsWithDelta($user->last_attempt_at, now(), 2);
    }

    public function test_the_account_of_the_user_gets_locked_when_the_user_has_exceeded_the_maximum_attempts_for_providing_the_correct_two_factor_code()
    {
        $user = User::factory()->create([
            'two_factor_enabled' => true,
            'two_factor_code' => '426897',
            'two_factor_expires_at' => now()->addMinutes(5),
            'two_factor_attempts' => 4,
            'last_attempt_at' => now()->subSeconds(10),
        ]);

        $this->actingAs($user)->post(route('verify.store'), [
            'two_factor_code' => '123456',
        ])->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'error' => 'Too many failed attempts. This account will now be locked for a period of 10 minutes.',
            ]);

        $user->refresh();

        $this->assertEqualsWithDelta($user->locked_until, now()->addMinutes(10), 1);
        $this->assertNull($user->two_factor_code);
        $this->assertNull($user->two_factor_expires_at);
    }

    public function test_a_user_with_two_factor_enabled_gets_signed_out_when_they_have_exceeded_the_maximum_attempts_for_providing_the_correct_two_factor_code()
    {
        $user = User::factory()->create([
            'two_factor_enabled' => true,
            'two_factor_code' => '426897',
            'two_factor_expires_at' => now()->addMinutes(5),
            'two_factor_attempts' => 4,
            'last_attempt_at' => now()->subSeconds(10),
        ]);

        $this->actingAs($user)->post(route('verify.store'), [
            'two_factor_code' => '123456',
        ])->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'error' => 'Too many failed attempts. This account will now be locked for a period of 10 minutes.',
            ]);

        $this->assertGuest();
    }

    public function test_the_two_factor_attempts_counter_gets_reset_when_the_user_provides_the_correct_two_factor_code()
    {
        $user = User::factory()->withTwoFactorEnabled()->create([
            'created_at' => now()->subMinutes(10),
            'updated_at' => now()->subMinutes(10),
            'two_factor_attempts' => 3,
            'last_attempt_at' => now()->subMinutes(6), 
        ]);

        $this->actingAs($user)->post(route('verify.store'), [
            'two_factor_code' => $user->two_factor_code,
        ]);

        $this->assertNull($user->last_attempt_at);
        $this->assertEquals($user->two_factor_attempt, 0);
    }

    public function test_the_two_factor_code_of_a_user_with_two_factor_enabled_are_reset_after_a_successful_two_factor_verification()
    {
        $user = User::factory()->withTwoFactorEnabled()->create([
            'two_factor_attempts' => 1,
            'last_attempt_at' => now()->subSeconds(30),
        ]);

        $this->actingAs($user)->post(route('verify.store'), [
            'two_factor_code' => $user->two_factor_code,
        ]);

        $this->assertNull($user->two_factor_code);
        $this->assertNull($user->two_factor_expires_at);
    }

    public function test_the_user_with_two_factor_enabled_gets_redirected_to_home_after_a_successful_two_factor_verification()
    {
        $user = User::factory()->withTwoFactorEnabled()->create([
            'two_factor_attempts' => 1,
            'last_attempt_at' => now()->subSeconds(30),
        ]);

        $this->actingAs($user)->post(route('verify.store'), [
            'two_factor_code' => $user->two_factor_code,
        ])->assertRedirect(route('home'));
    }

    public function test_a_guest_cannot_resend_a_two_factor_code()
    {
        $this->get(route('verify.resend'))
            ->assertRedirect(route('login'));

        Notification::assertNothingSent();
    }

    public function test_a_user_with_two_factor_authentication_can_resend_a_two_factor_code()
    {
        $user = User::factory()->create([
            'two_factor_enabled' => true,
            'two_factor_code' => '426897',
            'two_factor_expires_at' => now()->addMinutes(5),
        ]);

        $oldCode = $user->two_factor_code;

        $this->actingAs($user)->get(route('verify.resend'))
            ->assertRedirect(route('verify.create'));

        Notification::assertSentTo($user, TwoFactorCodeNotification::class);
        $this->assertNotEquals($user->fresh()->two_factor_code, $oldCode);
    }
}
