<?php

namespace Tests\Feature\Controllers\Auth;

use App\Models\User;
use App\Notifications\NewTokenRequestedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RequestNewTokenControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    
    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->expiredToken()->create();
    }

    public function test_an_authenticated_user_cannot_reach_request_new_token_page()
    {
        $token = $this->user->password_token;

        $this->actingAs($this->employee)->get(route('request-token.create', $token))
            ->assertRedirect(route('home'));
    }

    public function test_a_guest_needs_a_valid_token_to_reach_the_request_new_token_page()
    {
        $token = [
            'password_token' => 'erewbwbuq'
        ];

        $this->get(route('request-token.create', $token))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['error' => 'The selected token is invalid.']);
    }

    public function test_a_guest_cannot_reach_the_request_new_token_page_if_the_password_token_has_not_yet_expired()
    {
        $extraUser = User::factory()->notActivated()->create();

        $this->get(route('request-token.create', $extraUser->password_token))
            ->assertRedirect(route('activate-account.create', $extraUser->password_token))
            ->assertSessionHasErrors([
                'error' => 'The current token has not expired yet.'
            ]);
    }

    public function test_a_guest_with_an_expired_token_can_reach_the_request_new_token_page()
    {
        $token = $this->user->password_token;

        $this->assertNotNull($token);

        $this->get(route('request-token.create', $token))
            ->assertOk();
    }

    public function test_the_password_token_and_email_fields_are_required_to_request_a_new_password_token()
    {
        $data = [
            'token' => '',
            'email' => '',
        ];

        $this->post(route('request-token.store'), $data)
            ->assertSessionHasErrors([
                'token' => 'The token field is required.',
                'email' => 'The email field is required.'
            ]);
    }

    public function test_a_guest_cannot_request_a_new_password_token_by_providing_an_invalid_password_token()
    {
        $data = [
            'token' => 'Suspendisse1ac2massa3vestibulum4',
            'email' => $this->user->email,
        ];

        $this->post(route('request-token.store'), $data)
            ->assertSessionHasErrors([
                'token' => 'The selected token is invalid.',
            ]);

        $this->assertFalse($this->user->wasChanged('token_expires_at'));
    }

    public function test_a_guest_cannot_request_a_new_password_token_by_providing_an_invalid_email_address()
    {
        $data = [
            'token' => $this->user->password_token,
            'email' => 'hallo',
        ];

        $this->post(route('request-token.store'), $data)
            ->assertSessionHasErrors([
                'email' => 'The email field must be a valid email address.',
            ]);

        $this->assertFalse($this->user->wasChanged('token_expires_at'));
    }

    public function test_a_guest_must_provide_an_existing_email_address_to_request_a_new_password_token()
    {
        $data = [
            'token' => $this->user->password_token,
            'email' => 'other.user@example.com',
        ];

        $this->post(route('request-token.store'), $data)
            ->assertSessionHasErrors([
                'email' => 'The selected email is invalid.',
            ]);

        $this->assertFalse($this->user->wasChanged('token_expires_at'));
    }

    public function test_a_guest_cannot_request_a_new_password_token_when_the_provided_token_and_email_address_dont_match_their_account()
    {
        $extraUser = User::factory()->expiredToken()->create();

        $data = [
            'token' => $this->user->password_token,
            'email' => $extraUser->email
        ];

        $this->post(route('request-token.store'), $data)
            ->assertSessionHasErrors([
                'email' => 'The selected email is invalid.',
            ]);

        $this->assertFalse($this->user->wasChanged('token_expires_at'));
    }

    public function test_a_guest_can_only_request_a_new_password_token_when_the_current_password_token_has_expired()
    {
        $extraUser = User::factory()->notActivated()->create();

        $data = [
            'token' => $extraUser->password_token,
            'email' => $extraUser->email,
        ];

        $this->post(route('request-token.store'), $data)
            ->assertSessionHasErrors([
                'error' => 'This token has not yet expired.'
            ]);

        $this->assertFalse($this->user->wasChanged('token_expires_at'));
    }

    public function test_when_a_guest_submits_a_new_password_token_request_a_new_token_will_be_generated()
    {
        $data = [
            'token' => $this->user->password_token,
            'email' => $this->user->email,
        ];

        $this->post(route('request-token.store'), $data)
            ->assertRedirect(route('login'))
            ->assertSessionHas('success', 'You have requested a new token. Please check your mail for the new token.');
    }

    public function test_when_a_guest_submits_a_new_password_token_request_this_account_will_receive_a_notification()
    {
        Notification::fake();

        $data = [
            'token' => $this->user->password_token,
            'email' => $this->user->email,
        ];

        $this->post(route('request-token.store'), $data)
            ->assertRedirect(route('login'));

        Notification::assertCount(1);
        Notification::assertSentTo($this->user, NewTokenRequestedNotification::class);
    }
}
