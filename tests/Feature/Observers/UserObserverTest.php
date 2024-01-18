<?php

namespace Tests\Feature\Observers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserObserverTest extends TestCase
{
    use RefreshDatabase;

    protected $userData;
    protected $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->ActingAsVerifiedTwoFactor();

        $this->userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ];

        $this->user = User::factory()->create()->assignRole('User');
    }

    public function test_it_does_not_enabled_two_factor_when_an_employee_account_has_been_created()
    {
        $data = array_merge($this->userData, [
            'role' => 4
        ]);

        $this->post(route('users.store'), $data);

        $user = User::where('email', $data['email'])->first();

        $this->assertFalse($user->two_factor_enabled);
    }

    public function test_it_does_not_enable_two_factor_when_a_manager_account_has_been_created()
    {
        $data = array_merge($this->userData, [
            'role' => 3
        ]);

        $this->post(route('users.store'), $data);

        $user = User::where('email', $data['email'])->first();

        $this->assertFalse($user->two_factor_enabled);
    }

    public function test_it_does_enable_two_factor_when_an_admin_account_has_been_created()
    {
        $data = array_merge($this->userData, [
            'role' => 2
        ]);

        $this->post(route('users.store'), $data);

        $user = User::where('email', $data['email'])->first();

        $this->assertFalse($user->two_factor_enabled);
    }

    public function test_it_does_not_enable_two_factor_when_the_role_gets_changed_to_employee()
    {
        $data = [
            'name' => $this->user->name,
            'email' => $this->user->email,
            'role' => '4', // Employee
        ];

        $this->put(route('users.update', $this->user), $data)
            ->assertRedirect();

        $this->assertTrue($this->user->fresh()->hasRole('Employee'));
        $this->assertFalse($this->user->fresh()->two_factor_enabled);
    }

    public function test_it_does_not_enable_two_factor_when_the_role_gets_changed_to_manager()
    {
        $data = [
            'name' => $this->user->name,
            'email' => $this->user->email,
            'role' => '3', // Manager
        ];

        $this->put(route('users.update', $this->user), $data)
            ->assertRedirect();

        $this->assertTrue($this->user->fresh()->hasRole('Manager'));
        $this->assertFalse($this->user->fresh()->two_factor_enabled);
    }

    public function test_it_does_enable_two_factor_when_the_role_gets_changed_to_admin()
    {
        $data = [
            'name' => $this->user->name,
            'email' => $this->user->email,
            'role' => '2', // Admin
        ];

        $this->put(route('users.update', $this->user), $data)
            ->assertRedirect();

        $this->assertTrue($this->user->fresh()->hasRole('Admin'));
        $this->assertFalse($this->user->fresh()->two_factor_enabled);
    }

    public function test_it_does_not_disable_two_factor_when_the_role_of_the_user_has_changed_from_admin_to_employee()
    {
        $admin = User::factory()->create()->assignRole('Admin');

        $data = [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => '4', // Employee
        ];

        $this->put(route('users.update', $admin), $data)
            ->assertRedirect();

        $this->assertTrue($admin->fresh()->hasRole('Employee'));
        $this->assertFalse($admin->fresh()->two_factor_enabled);
    }

    protected function ActingAsVerifiedTwoFactor()
    {
        $this->actingAs($this->admin);

        $this->post(route('verify.store'), [
            'two_factor_code' => $this->admin->two_factor_code,
        ]);
    }
}
