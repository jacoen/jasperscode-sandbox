<?php

namespace Tests\Feature\Middleware;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TwoFactorMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_allows_access_for_logged_in_user_without_two_factor_authentication_to_continue()
    {
        $this->actingAs($this->user)->get(route('home'))
            ->assertOk();
    }

    public function test_it_redirects_a_user_without_2fa_enabled_to_the_home_route_with_an_error_message_when_they_try_to_interact_with_the_two_factor_verification_routes()
    {
        $this->actingAs($this->user);

        $form = $this->get(route('verify.create'));
        $this->assertTwoFactorError($form);

        $submit = $this->post(route('verify.store'), [
            'two_factor_code' => '123456',
        ]);
        $this->assertTwoFactorError($submit);

        $resend = $this->get(route('verify.resend'));
        $this->assertTwoFactorError($resend);
    }

    public function it_redirects_a_user_with_2fa_enabled_but_without_two_factor_code_to_the_home_route_with_an_error_message_when_they_try_to_interact_with_the_two_factor_verification_routes()
    {
        $user = User::factory()->create([
            'two_factor_enabled' => true,
        ]);

        $this->actingAs($user);

        $form = $this->get(route('verify.create'));
        $this->assertTwoFactorError($form);

        $submit = $this->post(route('verify.store'), [
            'two_factor_code' => '123456',
        ]);
        $this->assertTwoFactorError($submit);

        $resend = $this->get(route('verify.resend'));
        $this->assertTwoFactorError($resend);
    }

    public function test_it_redirects_a_user_with_2fa_but_without_a_two_factor_code_when_they_try_to_verify_a_two_factor_code()
    {
        $user = User::factory()->create([
            'two_factor_enabled' => true,
        ]);

        $response = $this->actingAs($user)->post(route('verify.store'), [
            'two_factor_code' => '123456',
        ]);

        $this->assertTwoFactorError($response);
    }

    public function test_it_redirects_a_user_with_2fa_but_without_a_two_factor_code_when_they_try_to_resend_a_new_two_factor_code()
    {
        $user = User::factory()->create([
            'two_factor_enabled' => true,
        ]);

        $response = $this->actingAs($user)->get(route('verify.resend'));
        $this->assertTwoFactorError($response);
    }

    private function assertTwoFactorError($response)
    {
        $response->assertRedirect(route('home'))
            ->withErrors([
                'error' => 'Could not verify your two factor because you have not enabled two factor authentication or you have no two factor code.',
            ]);
    }
}
