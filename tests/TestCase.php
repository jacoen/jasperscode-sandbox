<?php

namespace Tests;

use App\Models\User;
use Database\Seeders\PermissionRoleSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected $admin, $manager, $employee;

    public function setUp(): Void
    {
        parent::setUp();

        $this->seed(PermissionRoleSeeder::class);

        $this->admin = User::factory()->create()->syncRoles(['Admin']);
        $this->manager = User::factory()->create()->syncRoles(['Manager']);
        $this->employee = User::factory()->create()->syncRoles(['Employee']);
        $this->user = User::factory()->create()->syncRoles(['User']);
    }
}
