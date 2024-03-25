<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

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
        Permission::firstOrCreate(['name' => 'update project']);
        Permission::firstOrCreate(['name' => 'delete project']);
        Permission::firstOrCreate(['name' => 'pin project']);
        Permission::firstOrCreate(['name' => 'restore project']);
        
        Permission::firstOrCreate(['name' => 'read task']);
        Permission::firstOrCreate(['name' => 'create task']);
        Permission::firstOrCreate(['name' => 'update task']);
        Permission::firstOrCreate(['name' => 'delete task']);
        Permission::firstOrCreate(['name' => 'restore task']);
        
        Permission::firstOrCreate(['name' => 'read activity']);
        Permission::firstOrCreate(['name' => 'read expired projects']);

        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin']);
        $admin = Role::firstOrCreate(['name' => 'Admin']);
        $manager = Role::firstOrCreate(['name' => 'Manager']);
        $employee = Role::firstOrCreate(['name' => 'Employee']);
        $user = Role::firstOrcreate(['name' => 'User']);

        $admin->givePermissionTo([
            'read user', 'create user', 'edit user', 'delete user',
            'read project', 'create project', 'update project', 'delete project', 'pin project',
            'restore project',
            'read task', 'create task', 'update task', 'delete task', 'restore task',
            'read activity', 'read expired projects'
        ]);

        $manager->givePermissionTo([
            'read project', 'create project', 'update project', 'delete project',
            'read task', 'create task', 'update task', 'delete task', 'restore task',
            'read expired projects'
        ]);

        $employee->givePermissionTo([
            'read project',
            'read task', 'create task', 'update task', 'delete task',
        ]);

    }
}
