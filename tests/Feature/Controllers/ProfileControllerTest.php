<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_needs_to_confirm_their_password_before_they_can_reach_the_profile_page()
    {
        $this->actingAs($this->employee)->get(route('profile.show'))
            ->assertRedirect(route('password.confirm'));

        $this->setUpPasswordConfirmation($this->employee);

        $this->get(route('password.confirm'))
            ->assertOk();
    }

    public function test_a_user_can_see_if_two_factor_authentication_is_on_their_profile_page()
    {
        $this->setUpPasswordConfirmation($this->employee);

        $this->get(route('profile.show'))
            ->assertOk()
            ->assertSeeText([
                'Two factor authentication is disabled',
                'Enable two factor',
            ]);
    }

    public function test_an_admin_cannot_disable_two_factor_authentication_on_their_profile_page()
    {
        $this->actingAs($this->admin);

        $this->post(route('verify.store'), [
            'two_factor_code' => $this->admin->two_factor_code,
        ]);

        $this->setUpPasswordConfirmation($this->admin);

        $this->put(route('two-factor.update'))
            ->assertRedirect(route('profile.show'))
            ->assertSessionHasErrors([
                'error' => 'You cannot disable the two factor authentication.'
            ]);

        $this->assertTrue($this->admin->fresh()->two_factor_enabled);
    }

    public function test_a_user_can_enable_two_factor_authentication_on_their_profile_page()
    {
        $this->setUpPasswordConfirmation($this->employee);

        $this->put(route('two-factor.update'))
            ->assertRedirect('login')
            ->assertSessionHas('success', 'Two factor authentication has been enabled. Please sign in again.');

        $this->assertGuest();
        $this->assertTrue($this->employee->two_factor_enabled);
    }

    public function test_a_user_can_disabled_two_factor_authentication_on_their_profile_page()
    {
        $user = User::factory()->create(['two_factor_enabled' => true])->assignRole('Employee');

        $this->setUpPasswordConfirmation($user);

        $this->put(route('two-factor.update'))
            ->assertRedirect(route('profile.show'))
            ->assertSessionHas('success', 'The two factor authentication has been disabled.');

        $this->assertFalse($user->fresh()->two_factor_enabled);
    }

    protected function setUpPasswordConfirmation($user)
    {
        $this->actingAs($user)->post(route('password.confirm'), [
            'password' => 'password',
        ]);
    }
}
