<?php

namespace Tests\Feature\Listeners;

use App\Events\RoleUpdatedEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleUpdatedListenerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_it_enables_two_factor_when_a_user_with_the_super_admin_role_has_been_created()
    {
        $this->user->assignRole('Super Admin');
        event(new RoleUpdatedEvent($this->user));

        $this->assertTrue($this->user->fresh()->two_factor_enabled);
    }

    public function test_it_enables_two_factor_when_a_user_with_the_admin_role_has_been_created()
    {
        $this->user->assignRole('Admin');
        event(new RoleUpdatedEvent($this->user));

        $this->assertTrue($this->user->fresh()->two_factor_enabled);
    }

    public function test_it_does_not_enable_two_factor_when_a_user_with_the_manager_role_has_been_created()
    {
        $this->user->assignRole('Manager');
        event(new RoleUpdatedEvent($this->user));

        $this->assertfalse($this->user->fresh()->two_factor_enabled);
    }

    public function test_it_does_not_enable_two_factor_when_a_user_with_the_employee_role_has_been_created()
    {
        $this->user->assignRole('Employee');
        event(new RoleUpdatedEvent($this->user));

        $this->assertfalse($this->user->fresh()->two_factor_enabled);
    }

    public function test_it_does_enable_two_factor_when_the_role_of_the_user_gets_updated_to_super_admin()
    {
        $user = User::factory()->create()->assignRole('Manager');

        $user->syncRoles(['Super Admin']);

        event(new RoleUpdatedEvent($user));

        $this->assertTrue($user->fresh()->hasRole('Super Admin'));
        $this->assertTrue($user->fresh()->two_factor_enabled);
    }

    public function test_it_does_enable_two_factor_when_the_role_of_the_user_gets_updated_to_admin()
    {
        $user = User::factory()->create()->assignRole('Manager');

        $user->syncRoles(['Admin']);

        event(new RoleUpdatedEvent($user));

        $this->assertTrue($user->fresh()->hasRole('Admin'));
        $this->assertTrue($user->fresh()->two_factor_enabled);
    }

    public function test_it_does_not_disable_two_factor_when_the_role_of_a_user_with_the_admin_role_gets_changed()
    {

        $user = User::factory()->create(['two_factor_enabled' => true])->assignRole('Admin');

        $user->syncRoles(['Employee']);

        event(new RoleUpdatedEvent($user));

        $this->assertTrue($user->fresh()->hasRole('Employee'));
        $this->assertTrue($user->fresh()->two_factor_enabled);
    }
}
