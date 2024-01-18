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

        $this->admin = User::factory()->withTwoFactorEnabled()->create()->assignRole('Admin');
        $this->manager = User::factory()->create()->assignRole('Manager');
        $this->employee = User::factory()->create()->assignRole('Employee');
        $this->user = User::factory()->create()->assignRole('User');
    }
}
