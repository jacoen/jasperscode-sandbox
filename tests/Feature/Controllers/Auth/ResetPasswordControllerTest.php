<?php

namespace Tests\Feature\Controllers\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class ResetPasswordControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $data;

    public function setUp(): void
    {
        parent::setUp();

        $this->data = [
            'email' => 'david.smith@example.com',
            'password' => 'W#lkom123',
            'password_confirmation' => 'W#lkom123',
        ];
    }

    public function test_a_user_can_reset_their_password()
    {
        $user = User::factory()->create(['email' => $this->data['email']])->assignRole('User');

        $token = Password::broker()->createToken($user);

        $this->post(route('password.update'),  array_merge($this->data, ['token' => $token]))
            ->assertRedirect();

        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->fresh()->updated_at->gt(now()->subMinute()));
    }

    public function test_two_factor_code_will_be_generated_when_a_user_with_two_factor_enabled_reset_their_password()
    {
        $user = User::factory()->create(['email' => $this->data['email'], 'two_factor_enabled' => true])->assignRole('User');

        $token = Password::broker()->createToken($user);

        $this->post(route('password.update'),  array_merge($this->data, ['token' => $token]))
            ->assertRedirect();

        $user->refresh();
        $this->assertNotNull($user->two_factor_code);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'two_factor_code' => $user->two_factor_code]);
    }

    public function test_a_timestamp_for_the_two_factor_code_will_be_generated_when_a_user_with_two_factor_enabled_resets_their_password()
    {
        $user = User::factory()->create(['email' => $this->data['email'], 'two_factor_enabled' => true])->assignRole('User');

        $token = Password::broker()->createToken($user);

        $this->post(route('password.update'),  array_merge($this->data, ['token' => $token]))
            ->assertRedirect();

        $user->refresh();
        $this->assertNotNull($user->two_factor_expires_at);
        $this->assertEqualsWithDelta($user->two_factor_expires_at, now()->addMinutes(5), 1);
    }

    public function test_no_two_factor_code_will_be_generated_when_a_user_with_two_factor_disabled_resets_their_password()
    {
        $user = User::factory()->create(['email' => $this->data['email']])->assignRole('User');

        $token = Password::broker()->createToken($user);

        $this->post(route('password.update'),  array_merge($this->data, ['token' => $token]))
            ->assertRedirect();

        $this->assertNull($user->fresh()->two_factor_code);
    }
}
