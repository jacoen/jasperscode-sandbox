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

    public function test_a_guest_cannot_submit_a_two_factor_code()
    {
        $this->post(route('verify.store'), [
            'two_factor_code' => 123456,
        ])->assertRedirect(route('login'));
    }

    public function test_a_user_with_two_factor_autentication_can_visit_the_two_factor_verification_page_and_sees_a_masked_email_address()
    {
        $user = User::factory()->withTwoFactorEnabled()->create([
            'email' => 'david.smith@example.com',
        ]);

        $this->actingAs($user)->get(route('verify.create'))
            ->assertOk()
            ->assertSeeText([
                'email' => 'd********@example.com',
            ]);
    }

    public function test_the_two_factor_code_is_required()
    {
        $user = User::factory()->withTwoFactorEnabled()->create();

        $this->actingAs($user)->post(route('verify.store'), [
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
        $user = User::factory()->withTwoFactorEnabled()->create();

        $this->actingAs($user)->post(route('verify.store'), [
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
        $user = User::factory()->withTwoFactorEnabled()->create();

        $this->actingAs($user)->post(route('verify.store'), [
            'two_factor_code' => '12345'
        ])->assertSessionHasErrors([
            'two_factor_code' => 'The two factor code field must be 6 digits.',
        ]);

        $this->actingAs($user)->post(route('verify.store'), [
            'two_factor_code' => '1234567'
        ])->assertSessionHasErrors([
            'two_factor_code' => 'The two factor code field must be 6 digits.',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'two_factor_code' => $this->user->two_factor_code,
        ]);
    }

    public function test_the_user_gets_redirected_to_the_home_route_and_their_two_factor_code_get_erased_after_they_enter_their_two_factor_code_correctly()
    {
        $user = User::factory()->withTwoFactorEnabled()->create([
            'two_factor_attempts' => 1,
            'last_attempt_at' => now()->subSeconds(30),
        ]);

        $this->actingAs($user)->post(route('verify.store'), [
            'two_factor_code' => $user->two_factor_code,
        ])->assertRedirect(route('home'));

        $user->refresh();

        $this->assertNull($user->two_factor_code);
        $this->assertNull($user->two_factor_expires_at);
        $this->assertEquals(0, $user->login_attempt);
        $this->assertNull($user->last_attempt_at);
    }

    public function test_user_will_receive_an_error_message_when_enter_an_invalid_two_factor_code()
    {
        $user = User::factory()->withTwoFactorEnabled()->create([
            'two_factor_code' => '197382',
        ]);

        $this->actingAs($user)->post(route('verify.store'), [
            'two_factor_code' => 123456,
        ])->assertSessionHasErrors([
            'two_factor_code' => 'The two factor code you have entered does not match.',
        ]);

        $user->refresh();
        $this->assertNotNull($user->two_factor_code);
        $this->assertNotNull($user->two_factor_expires_at);
        $this->assertEquals(1, $user->two_factor_attempts);
        $this->assertEqualsWithDelta($user->last_attempt_at, now(), 2);
    }

    public function test_the_user_gets_signed_out_and_its_account_gets_locked_when_user_has_exceeded_the_maximum_attempts_for_proving_the_correct_two_factor_code()
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

        $this->assertGuest();
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
