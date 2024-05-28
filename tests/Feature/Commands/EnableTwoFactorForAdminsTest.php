<?php

namespace Tests\Feature\Commands;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnableTwoFactorForAdminsTest extends TestCase
{
    use RefreshDatabase;

    protected $completeAdmin;

    public function setUp(): void
    {
        parent::setUp();

        $this->completeAdmin = User::factory()->withTwoFactorEnabled()->create([
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ])->assignRole('Admin');
    }

    public function test_it_activates_two_factor_for_the_super_admin_account_when_its_not_enabled_yet()
    {
        $superAdmin = User::factory()->superAdmin()->create([
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ]);

        $this->artisan('2fa:enableForAdmins')->assertSuccessful();

        $this->assertTrue($superAdmin->fresh()->two_factor_enabled);
        $this->assertEqualsWithDelta($superAdmin->fresh()->updated_at, now(), 1);
    }

    public function test_it_activates_two_factor_for_the_admin_account_when_its_not_enabled_yet()
    {
        $admin = User::factory()->create()->assignRole('Admin');

        $this->artisan('2fa:enableForAdmins')->assertSuccessful();
        
        $this->assertTrue($admin->fresh()->two_factor_enabled);
        $this->assertEqualsWithDelta($admin->updated_at, now(), 1);
    }

    public function test_it_does_not_update_the_admin_account_when_two_factor_has_been_enabled_already()
    {
        $this->assertTrue($this->completeAdmin->two_factor_enabled);

        $this->artisan('2fa:enableForAdmins')->assertSuccessful();

        $this->assertNotEqualsWithDelta($this->completeAdmin->fresh()->updated_at, now(), 1);
    }

    public function test_it_does_not_update_the_the_account_of_a_manager()
    {
        $this->assertFalse($this->manager->two_factor_enabled);

        $this->artisan('2fa:enableForAdmins')->assertSuccessful();

        $this->assertFalse($this->manager->fresh()->two_factor_enabled);
    }

    public function test_it_does_not_update_the_account_of_an_employee()
    {
        $this->assertFalse($this->employee->two_factor_enabled);

        $this->artisan('2fa:enableForAdmins')->assertSuccessful();

        $this->assertFalse($this->employee->fresh()->two_factor_enabled);
    }
}
