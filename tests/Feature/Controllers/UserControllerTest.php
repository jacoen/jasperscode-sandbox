<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Notifications\AccountCreatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $data;
    protected $employeeRole;

    public function setUp(): void
    {
        parent::setUp();

        $this->data = [
            'name' => 'David Smith',
            'email' => 'david.smith@example.com',
        ];

        $this->employeeRole = Role::where('name', 'Employee')->first();
    }

    public function test_guests_cannot_visit_the_user_overview_page()
    {
        $this->get(route('users.index'))->assertRedirect(route('login'));
    }

    public function test_a_user_without_the_read_user_permission_cannot_visit_the_user_overview_page()
    {
        $this->actingAs($this->employee)->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_a_user_with_the_read_user_permission_can_visit_the_user_overview_page()
    {
        $this->actingAs($this->admin)->get(route('users.index'))
            ->assertOk()
            ->assertSeeText([
                $this->admin->name, $this->admin->email, 'Admin',
                $this->manager->name, $this->manager->email, 'Manager',
                $this->employee->name, $this->employee->email, 'Employee',
                $this->user->name, $this->user->email,
            ]);
    }

    public function test_a_guest_cannot_visit_the_create_user_form()
    {
        $this->get(route('users.create'))->assertRedirect(route('login'));
    }

    public function test_a_guest_cannot_create_a_new_user()
    {
        $this->post(route('users.store'))->assertRedirect(route('login'));

        $this->assertDatabaseMissing('users', $this->data);
    }

    public function test_a_user_without_the_create_user_permission_cannot_visit_the_create_user_form()
    {
        $this->actingAs($this->user)->get(route('users.create'))
            ->assertForbidden();
    }

    public function test_a_user_without_the_create_user_permission_cannot_create_a_new_user()
    {
        $this->actingAs($this->user)->post(route('users.store'), $this->data)
            ->assertForbidden();

        $this->assertDatabaseMissing('users', $this->data);
    }

    public function test_a_user_with_the_create_user_permission_can_visit_the_create_user_form()
    {
        $this->actingAs($this->admin)->get(route('users.create'))
            ->assertOk();
    }

    public function test_a_user_with_the_create_user_permission_can_create_a_new_user()
    {
        $userData = array_merge($this->data, ['role' => $this->employeeRole->id]);

        $this->actingAs($this->admin)->post(route('users.store'), $this->data)
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success', 'A new user was created.');

        $user = User::latest()->first();

        
        $this->assertDatabaseHas('users', [
            'name' => $userData['name'],
            'email' => $userData['email'],
        ]); 
    }

    public function test_the_name_and_email_fields_are_required_when_creating_a_user()
    {
        $initialCount = User::count();

        $this->actingAs($this->admin);

        $data = [
            'name' => '',
            'email' => '',
        ];

        $this->post(route('users.store'), $data)
            ->assertSessionHasErrors([
                'name' => 'The name field is required.',
                'email' => 'The email field is required.',
            ]);

        $this->assertEquals($initialCount, User::count());
    }

    public function test_the_email_address_must_be_valid_when_creating_a_user_account()
    {
        $this->actingAs($this->admin);
        
        $userData = array_merge($this->data, ['email' => 'hallo']);
    
        $this->post(route('users.store'), $userData)
            ->assertSessionHasErrors([
                'email' => 'The email field must be a valid email address.',
            ]);
    
        $this->assertDatabaseMissing('users', $userData);
    }

    public function test_the_email_of_a_user_must_be_unique()
    {
        $this->actingAs($this->admin);

        User::factory()->create(['name' => 'John Doe Senior', 'email' => 'john@example.com']);

        $userData = array_merge($this->data, [
            'email' => 'john@example.com',
            'role' => 4,
        ]);

        $this->post(route('users.store'), $userData)
            ->assertSessionHasErrors([
                'email' => 'The email has already been taken.',
            ]);

        $this->assertDatabaseMissing('users', [
            'name' => $userData['name'],
            'email' => $userData['email'],
        ]);
    }

    public function test_a_user_must_be_assigned_a_valid_role()
    {
        $this->actingAs($this->admin);

        $userData = array_merge($this->data, ['role' => 99]);

        $this->post(route('users.store'), $userData)
            ->assertSessionHasErrors([
                'role' => 'The selected role is invalid.',
            ]);

        $this->assertDatabaseMissing('users', [
            'name' => $userData['name'],
            'email' => $userData['email'],
        ]);
    }

    public function test_a_guest_cannot_visit_the_edit_user_form()
    {
        $user = User::factory()->create()->assignRole('User');

        $this->get(route('users.edit', $user))->assertRedirect(route('login'));
    }

    public function test_a_guest_cannot_edit_an_existing_user()
    {
        $user = User::factory()->create()->assignRole('User');

        $userData = array_merge($this->data, ['role' => $this->employeeRole->id]);

        $this->put(route('users.update', $user), $userData)->assertRedirect(route('login'));

        $this->assertFalse($user->fresh()->hasRole('Employee'));
        $this->assertNotEquals($user->fresh()->name, $userData['name']);
        $this->assertNotEquals($user->fresh()->email, $userData['email']);
        $this->assertDatabaseMissing('users', array_merge(['id' => $user->id], $this->data));
    }

    public function test_a_user_without_the_edit_user_permission_cannot_visit_the_edit_user_form()
    {
        $user = User::factory()->create()->assignRole('User');
        $this->actingAs($this->user)->get(route('users.edit', $user))
            ->assertForbidden();
    }

    public function test_a_user_without_the_edit_user_permission_cannot_edit_an_existing_user()
    {
        $user = User::factory()->create()->assignRole('User');

        $this->actingAs($this->user)->put(route('users.update', $user), array_merge($this->data, ['role' => $this->employeeRole->id]))
            ->assertForbidden();

        $this->assertFalse($user->fresh()->hasRole('Employee'));
        $this->assertNotEquals($user->fresh()->name, $this->data['name']);
        $this->assertNotEquals($user->fresh()->email, $this->data['email']);
        $this->assertDatabaseMissing('users', array_merge(['id' => $user->id], $this->data));
    }

    public function test_a_user_with_the_edit_user_permission_can_visit_the_edit_user_form()
    {
        $user = User::factory()->create()->assignRole('User');

        $this->actingAs($this->admin)->get(route('users.edit', $user))
            ->assertOk();
    }

    public function test_a_user_with_the_edit_user_permission_can_update_an_existing_user()
    {
        $user = User::factory()->create(['email' => 'david.smith@example.com'])->assignRole('User');

        $this->actingAs($this->admin)->put(route('users.update', $user), array_merge($this->data, ['role' => $this->employeeRole->id]))
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success', $user->fresh()->name.'\'s account has been updated!');

        $user->refresh();

        $this->assertEquals($user->name, $this->data['name']);
        $this->assertEquals($user->email, $this->data['email']);
        $this->assertTrue($user->hasRole('Employee'));
    }

    public function test_all_fields_are_required_when_updating_an_existing_user()
    {
        $user = User::factory()->create()->assignRole('User');

        $data = [
            'name' => '',
            'email' => '',
            'role' => '',
        ];

        $this->actingAs($this->admin)->put(route('users.update', $user), $data)
            ->assertSessionHasErrors([
                'name' => 'The name field is required.',
                'email' => 'The email field is required.',
                'role' => 'The role field is required.',
            ]);

        $user->refresh();

        $this->assertNotEquals($user->name, $data['name']);
        $this->assertNotEquals($user->email, $data['email']);
        $this->assertNotEquals($user->roles->first()->id, $data['role']);
    }

    public function test_the_name_field_must_at_least_contain_5_characters_when_updating_a_user()
    {
        $user = User::factory()->create(['email' => 'john@example.com'])->assignRole('User');

        $data =  [
            'name' => 'abe',
            'email' => $user->email,
            'role' => $user->roles()->first()->id,
        ];

        $this->actingAs($this->admin)->put(route('users.update', $user), $data)
            ->assertSessionHasErrors([
                'name' => 'The name field must be at least 5 characters.',
            ]);

        $this->assertNotEquals($user->fresh()->name, $data['name']);
    }

    public function test_the_name_field_can_not_be_longer_than_255_characters_when_updating_a_user()
    {
        $user = User::factory()->create()->assignRole('User');

        $data = [
            'name' => Str::random(256),
            'email' => $user->email,
            'role' => $user->roles()->first()->id,
        ];

        $this->actingAs($this->admin)->put(route('users.update', $user), $data)
            ->assertSessionHasErrors([
                'name' => 'The name field must not be greater than 255 characters.',
            ]);

        $this->assertNotEquals($user->fresh()->name, $data['name']);
    }

    public function test_a_valid_email_address_must_be_provided_when_updating_an_existing_user()
    {
        $user = User::factory()->create()->assignRole('User');

        $data = [
            'name' => 'David Smith',
            'email' => 'david.smith',
            'role' => $user->roles()->first()->id,
        ];

        $this->actingAs($this->admin)->put(route('users.update', $user), $data)
            ->assertSessionHasErrors([
                'email' => 'The email field must be a valid email address.',
                
            ]);

        $this->assertNotEquals($user->fresh()->email, $data['email']);
    }

    public function test_the_email_address_cannot_be_duplicated_when_updating_an_existing_user()
    {
        $user = User::factory()->create()->assignRole('User');
        User::factory()->create(['email' => 'jane@example.com'])->assignRole('User');

        $data = [
            'name' => 'David Smith',
            'email' => 'jane@example.com',
            'role' => $user->roles()->first()->id,
        ];

        $this->actingAs($this->admin)->put(route('users.update', $user), $data)
            ->assertSessionHasErrors([
                'email' => 'The email has already been taken.',
                
            ]);

        $this->assertNotEquals($user->fresh()->email, $data['email']);
    }

    public function test_an_existing_role_must_be_provided_when_updating_an_existing_user()
    {
        $user = $user = User::factory()->create()->assignRole('User');

        $data = array_merge($this->data, ['role' => 99]);

        $this->actingAs($this->admin)->put(route('users.update', $user), $data)
            ->assertSessionHasErrors([
                'role' => 'The selected role is invalid.',
            ]);

        $this->assertNotEquals($user->roles()->first()->id, 99);
    }

    public function test_a_guest_cannot_delete_an_existing_user()
    {
        $user = User::factory()->create();

        $initialCount = User::count();

        $this->delete(route('users.destroy', $user))
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);

        $this->assertEquals($initialCount, User::count());
    }

    public function test_a_user_without_the_delete_user_permission_cannot_delete_an_existing_user()
    {
        $user = User::factory()->create()->assignRole('User');
        $secondUser = User::factory()->create();

        $initialCount = User::count();

        $this->actingAs($user)->delete(route('users.destroy', $secondUser))
            ->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $secondUser->id,
            'name' => $secondUser->name,
            'email' => $secondUser->email,
        ]);

        $this->assertEquals($initialCount, User::count());
    }

    public function test_a_user_with_the_delete_user_permission_can_delete_an_existing_user()
    {
        $user = User::factory()->create()->assignRole('Admin');
        $secondUser = User::factory()->create();

        $initialCount = User::count();

        $this->actingAs($user)->delete(route('users.destroy', $secondUser))
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success', 'User has been deleted!');

        $this->assertEquals($initialCount-1, User::count());
    }
}
