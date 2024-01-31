<?php

namespace Tests\Feature\Middleware;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TwoFactorMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected $request;
    protected $middleware;

    public function test_it_allows_access_for_logged_in_user_without_two_factor_authentication_to_continue()
    {
        $this->be($this->employee);

        $this->get(route('home'))
            ->assertOk();
    }

    public function test_it_redirects_to_verify_route_for_logged_in_user_with_two_factor_auth()
    {
        $user = User::factory()->create(['two_factor_enabled' => true]);

        $this->be($user);
        $user->generateTwoFactorCode();

        $this->get(route('home'))
            ->assertRedirect(route('verify.create'));
    }

    public function test_it_allows_user_with_two_factor_enabled_and_a_two_factor_code_access_to_verify_route()
    {
        $user = User::factory()->create(['two_factor_enabled' => true]);

        $this->be($user);
        $user->generateTwoFactorCode();

        $this->get(route('verify.create'))
            ->assertOk();
    }

    public function test_it_logs_out_user_with_expired_two_factor_code()
    {
        $user = User::factory()->create([
            'created_at' => now()->subMinutes(10),
            'updated_at' => now()->subMinutes(10),
            'two_factor_enabled' => true,
            'two_factor_code' => generateDigitCode(),
            'two_factor_expires_at' => now()->subMinute(),
        ]);

        $this->actingAs($user)->get(route('verify.create'))
            ->assertRedirect('login');
        
        $this->assertGuest();
    }

    public function test_it_does_not_allow_a_user_without_two_factor_enabled_access_to_the_verify_routes()
    {
        $this->actingAs($this->employee);

        $create = $this->get(route('verify.create'));
        $this->assertTwoFactorError($create);

        $post = $this->post(route('verify.store'), [
            'two_factor_code' => '',
        ]);
        $this->assertTwoFactorError($post);

        $resend = $this->get(route('verify.resend'));
        $this->assertTwoFactorError($resend);
    }

    public function test_it_does_not_allow_a_user_without_two_factor_code_access_to_the_verify_routes()
    {
        $this->actingAs($this->admin);

        $create = $this->get(route('verify.create'));
        $this->assertTwoFactorError($create);

        $post = $this->post(route('verify.store'), [
            'two_factor_code' => '',
        ]);
        $this->assertTwoFactorError($post);

        $resend = $this->get(route('verify.resend'));
        $this->assertTwoFactorError($resend);
    }

    private function assertTwoFactorError($response)
    {
        $response->assertRedirect(route('home'))
            ->withErrors([
                'error' => 'Could not verify your two factor because you have not enabled two factor authentication or you have no two factor code.'
            ]);   
    }
}
