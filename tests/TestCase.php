<?php

namespace Tests;

use App\Models\User;
use Database\Seeders\PermissionRoleSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected $admin;

    protected $manager;

    protected $employee;

    protected $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionRoleSeeder::class);

        $this->admin = User::factory()->create(['two_factor_enabled' => true])->syncRoles(['Admin']);
        $this->manager = User::factory()->create()->syncRoles(['Manager']);
        $this->employee = User::factory()->create()->syncRoles(['Employee']);
        $this->user = User::factory()->create()->syncRoles(['User']);
    }
}
