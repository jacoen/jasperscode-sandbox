<?php

namespace Tests\Feature\Observers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserObserverTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create()->assignRole('User');
    }

    public function test_it_does_not_enabled_two_factor_when_an_employee_account_has_been_created()
    {
        $employee = User::factory()->create()->assignRole('Employee');

        $this->assertFalse($employee->two_factor_enabled);
    }

    public function test_it_does_not_enable_two_factor_when_a_manager_account_has_been_created()
    {
        $manager = User::factory()->create()->assignRole('Manager');

        $this->assertFalse($manager->two_factor_enabled);
    }

    public function test_it_does_enabled_two_factor_when_an_admin_account_has_been_created()
    {
        $admin = User::factory()->create()->assignRole('Admin');

        $this->assertTrue($admin->two_factor_enabled);
    }

    public function test_it_does_not_enable_two_factor_when_the_role_gets_changed_to_employee()
    {
        $data = [
            'name' => $this->user->name,
            'email' => $this->user->email,
            'role' => '4', // Employee
        ];

        $this->actingAs($this->admin)->put(route('users.update', $this->user), $data)
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

        $this->actingAs($this->admin)->put(route('users.update', $this->user), $data)
            ->assertRedirect();

        $this->assertTrue($this->user->fresh()->hasRole('Employee'));
        $this->assertFalse($this->user->fresh()->two_factor_enabled);
    }

    public function test_it_does_enable_two_factor_when_the_role_gets_changed_to_admin()
    {
        $data = [
            'name' => $this->user->name,
            'email' => $this->user->email,
            'role' => '2', // Admin
        ];

        $this->actingAs($this->admin)->put(route('users.update', $this->user), $data)
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

        $this->actingAs($this->admin)->put(route('users.update', $admin), $data)
            ->assertRedirect();

        $this->assertTrue($admin->fresh()->hasRole('Employee'));
        $this->assertFalse($admin->fresh()->two_factor_enabled);
    }
}
