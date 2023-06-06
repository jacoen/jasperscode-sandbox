<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;

class PermissionRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::firstOrCreate(['name' => 'read user']);
        Permission::firstOrCreate(['name' => 'create user']);
        Permission::firstOrCreate(['name' => 'edit user']);
        Permission::firstOrCreate(['name' => 'delete user']);
        Permission::firstOrCreate(['name' => 'read project']);
        Permission::firstOrCreate(['name' => 'create project']);
        Permission::firstOrCreate(['name' => 'edit project']);
        Permission::firstOrCreate(['name' => 'delete project']);

        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin']);
        $admin = Role::firstOrCreate(['name' => 'Admin']);
        $manager = Role::firstOrCreate(['name' => 'Manager']);
        $employee = Role::firstOrCreate(['name' => 'Employee']);
        $user = Role::firstOrcreate(['name' => 'User']);

        $admin->givePermissionTo([
            'read user', 'create user', 'edit user', 'delete user',
            'read project', 'create project', 'edit project', 'delete project',
        ]);

        $manager->givePermissionTo([
            'read project', 'create project', 'edit project', 'delete project'
        ]);
    }
}
