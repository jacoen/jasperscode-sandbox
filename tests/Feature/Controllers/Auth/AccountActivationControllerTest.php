<?php

namespace Tests\Feature\Controllers\Auth;

use App\Models\User;
use App\Notifications\AccountActivatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AccountActivationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->notActivated()->create(['email' => 'scott.price@example.com'])->assignRole('User');

        Notification::fake();
    }

    public function test_an_authenticated_user_cannot_reach_the_account_activation_page()
    {
        $this->actingAs($this->user)->get(route('activate-account.create', $this->user))
            ->assertRedirect(route('home'));
    }

    public function test_a_guest_needs_a_valid_password_token_to_reach_the_account_activation_page()
    {
        $token = [
            'password_token' => 'erewbwbuq',
        ];

        $this->get(route('activate-account.create', $token))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['error' => 'The selected token is invalid.']);
    }

    public function test_all_fields_are_required_to_activate_the_user_account()
    {
        $data = [
            'password_token' => '',
            'email' => '',
            'password' => '',
            'password_confirmation' => '',
        ];

        $this->post(route('activate-account.store'), $data)
            ->assertSessionHasErrors([
                'password_token' => 'The password token field is required.',
                'email' => 'The email field is required.',
                'password' => 'The password field is required.',
            ]);

        $this->assertNotNull($this->user->fresh()->email);

        $this->assertNull($this->user->password_changed_at);
        $this->assertNotNull($this->user->password_token);
    }

    public function test_guest_must_provide_a_valid_password_token_to_activate_the_user_account()
    {
        $data = [
            'password_token' => 'Suspendisse1ac2massa3vestibulum4',
            'email' => $this->user->email,
            'password' => 'W#lkom123',
            'password_confirmation' => 'W#lkom123',
        ];

        $this->post(route('activate-account.store'), $data)
            ->assertSessionHasErrors([
                'password_token' => 'The selected password token is invalid.',
            ]);

        $this->assertNull($this->user->password_changed_at);
        $this->assertNotNull($this->user->password_token);
    }

    public function test_guest_must_enter_a_valid_email_address_to_acticate_the_user_account()
    {
        $data = [
            'password_token' => $this->user->password_token,
            'email' => 'hallo',
            'password' => bcrypt('W#lkom123'),
            'password_confirmation' => bcrypt('W#lkom123'),
        ];

        $this->post(route('activate-account.store'), $data)
            ->assertSessionHasErrors([
                'email' => 'The email field must be a valid email address.',
            ]);

        $this->assertNotEquals($this->user->fresh()->email, $data['email']);

        $this->assertNull($this->user->password_changed_at);
        $this->assertNotNull($this->user->password_token);
    }

    public function test_a_guest_must_enter_an_existing_email_address_to_activate_their_account()
    {
        $data = [
            'password_token' => $this->user->password_token,
            'email' => 'webbuilder.hello@example.com',
            'password' => 'W#lkom123',
            'password_confirmation' => 'W#lkom123',
        ];

        $this->post(route('activate-account.store'), $data)
            ->assertSessionHasErrors([
                'email' => 'The selected email is invalid.',
            ]);

        $this->assertNotEquals($this->user->fresh()->email, $data['email']);

        $this->assertNull($this->user->password_changed_at);
        $this->assertNotNull($this->user->password_token);
    }

    public function test_a_guest_must_enter_a_valid_password_to_activate_their_user_account()
    {
        $data = [
            'password_token' => $this->user->password_token,
            'email' => 'webbuilder.hello@example.com',
            'password' => 'hello',
            'password_confirmation' => 'W#lkom123',
        ];

        $this->post(route('activate-account.store'), $data)
            ->assertSessionHasErrors('password');

        $this->assertNull($this->user->password_changed_at);
        $this->assertNotNull($this->user->password_token);
    }

    public function test_a_guest_can_only_activate_their_account_if_the_password_token_and_email_address_matches_their_account()
    {
        $extraUser = User::factory()->notActivated()->create(['email' => 'franky.kinney@example.com']);

        $data = [
            'password_token' => $this->user->password_token,
            'email' => $extraUser->email,
            'password' => 'W#lkom123',
            'password_confirmation' => 'W#lkom123',
        ];

        $this->post(route('activate-account.store'), $data)
            ->assertSessionHasErrors([
                'email' => 'The selected email is invalid.',
            ]);

        $this->assertNull($this->user->password_changed_at);
        $this->assertNotNull($this->user->password_token);
    }

    public function test_a_guest_cannot_activate_their_account_when_the_password_token_has_expired()
    {
        $user = User::factory()->expiredToken()->create();

        $data = [
            'password_token' => $user->password_token,
            'email' => $user->email,
            'password' => 'W#lkom123',
            'password_confirmation' => 'W#lkom123',
        ];

        $this->post(route('activate-account.store'), $data)
            ->assertSessionHasErrors([
                'error' => 'The token has expired, click on the request new token button to request a new token.',
            ]);

        $this->assertNull($this->user->password_changed_at);
        $this->assertNotNull($this->user->password_token);
    }

    public function test_a_user_account_will_be_activated_when_the_guest_provides_valid_information_for_their_account()
    {
        $data = [
            'password_token' => $this->user->password_token,
            'email' => $this->user->email,
            'password' => 'W#lkom123',
            'password_confirmation' => 'W#lkom123',
        ];

        $this->post(route('activate-account.store'), $data)
            ->assertRedirect(route('login'))
            ->assertSessionHas('success', 'Your password has been changed.');

        $this->assertNotNull($this->user->fresh()->password_changed_at);
        $this->assertNull($this->user->fresh()->password_token);
    }

    public function test_when_an_account_has_been_activated_that_user_will_receive_a_message()
    {
        Notification::fake();

        $data = [
            'password_token' => $this->user->password_token,
            'email' => $this->user->email,
            'password' => 'W#lkom123',
            'password_confirmation' => 'W#lkom123',
        ];

        $this->post(route('activate-account.store'), $data)
            ->assertRedirect(route('login'));

        Notification::assertCount(1);
        Notification::assertSentTo($this->user, AccountActivatedNotification::class);
    }
}
