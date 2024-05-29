<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected array $data;

    protected Role $employeeRole;

    protected int $initialCount;

    public function setUp(): void
    {
        parent::setUp();

        $this->data = [
            'name' => 'David Smith',
            'email' => 'david.smith@example.com',
        ];

        $this->employeeRole = Role::where('name', 'Employee')->first();

        $this->initialCount = User::count();
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

    public function test_a_guest_cannot_visit_the_create_user_page()
    {
        $this->get(route('users.create'))->assertRedirect(route('login'));
    }

    public function test_a_user_without_the_create_user_permission_cannot_visit_the_create_user_page()
    {
        $this->actingAs($this->user)->get(route('users.create'))
            ->assertForbidden();
    }

    public function test_a_user_with_the_create_user_permission_can_visit_the_create_user_form()
    {
        $this->actingAs($this->admin)->get(route('users.create'))
            ->assertOk();
    }

    public function test_the_name_and_email_fields_are_required_when_creating_a_user()
    {
        $data = [
            'name' => '',
            'email' => '',
        ];

        $this->actingAs($this->admin)->post(route('users.store'), $data)
            ->assertSessionHasErrors([
                'name' => 'The name field is required.',
                'email' => 'The email field is required.',
            ]);

        $this->assertEquals($this->initialCount, User::count());
    }

    public function test_a_valid_email_address_must_be_provided_when_creating_a_user()
    {
        $userData = array_merge($this->data, ['email' => 'hallo']);

        $this->actingAs($this->admin)->post(route('users.store'), $userData)
            ->assertSessionHasErrors([
                'email' => 'The email field must be a valid email address.',
            ]);

        $this->assertEquals($this->initialCount, User::count());
    }

    public function test_the_email_of_a_user_must_be_unique()
    {
        User::factory()->create(['name' => 'John Doe Senior', 'email' => 'john@example.com']);

        $count = User::count();

        $userData = array_merge($this->data, [
            'email' => 'john@example.com',
            'role' => $this->employeeRole->id,
        ]);

        $this->actingAs($this->admin)->post(route('users.store'), $userData)
            ->assertSessionHasErrors([
                'email' => 'The email has already been taken.',
            ]);

        $this->assertEquals($count, User::count());
    }

    public function test_a_user_must_be_assigned_a_valid_role()
    {
        $userData = array_merge($this->data, ['role' => 99]);

        $this->actingAs($this->admin)->post(route('users.store'), $userData)
            ->assertSessionHasErrors([
                'role' => 'The selected role is invalid.',
            ]);

        $this->assertEquals($this->initialCount, User::count());
    }

    public function test_a_guest_cannot_create_a_new_user()
    {
        $this->post(route('users.store'), $this->data)->assertRedirect(route('login'));

        $this->assertEquals($this->initialCount, User::count());
    }

    public function test_a_user_without_the_create_user_permission_cannot_create_a_new_user()
    {
        $this->actingAs($this->user)->post(route('users.store'), $this->data)
            ->assertForbidden();

        $this->assertEquals($this->initialCount, User::count());
    }

    public function test_a_user_with_the_create_user_permission_can_create_a_new_user()
    {
        $this->actingAs($this->admin)->post(route('users.store'), $this->data)
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success', 'A new user has been created.');

        $user = User::latest('id')->first();

        $this->assertEquals($user->name, $this->data['name']);
        $this->assertEquals($user->email, $this->data['email']);
        $this->assertGreaterThan($this->initialCount, User::count());
    }

    public function test_a_guest_cannot_visit_the_edit_user_page()
    {
        $user = User::factory()->create()->assignRole('User');

        $this->get(route('users.edit', $user))->assertRedirect(route('login'));
    }

    public function test_a_user_without_the_edit_user_permission_cannot_visit_the_edit_user_page()
    {
        $user = User::factory()->create()->assignRole('User');

        $this->actingAs($this->user)->get(route('users.edit', $user))
            ->assertForbidden();
    }

    public function test_a_user_with_the_edit_user_permission_can_visit_the_edit_user_page()
    {
        $user = User::factory()->create()->assignRole('User');

        $this->actingAs($this->admin)->get(route('users.edit', $user))
            ->assertOk()
            ->assertSeeText($user->name);
    }

    public function test_all_the_fields_are_required_when_updating_a_user()
    {
        $user = User::factory()->create()->assignRole('User');

        $data = [
            'name' => '',
            'email' => '',
        ];

        $this->actingAs($this->admin)->put(route('users.update', $user), array_merge($data, [
            'role' => '',
        ]))->assertSessionHasErrors([
            'name' => 'The name field is required.',
            'email' => 'The email field is required.',
            'role' => 'The role field is required.',
        ]);

        $user->refresh();
        $this->assertNotNull($user->name);
        $this->assertNotNull($user->email);
        $this->assertNotEmpty($user->roles());
    }

    public function test_the_provided_name_must_be_at_least_5_characters_long_when_updating_a_user()
    {
        $user = User::factory()->create()->assignRole('User');

        $this->actingAs($this->admin)->put(route('users.update', $user), array_merge($this->data, [
            'name' => 'abcd',
            'role' => $this->employeeRole->id,
        ]))->assertSessionHasErrors([
            'name' => 'The name field must be at least 5 characters.',
        ]);

        $this->assertNotEquals($user->fresh()->name, 'abcd');
    }

    public function test_the_provided_name_cannot_be_longer_than_255_character_when_updating_a_user()
    {
        $user = User::factory()->create()->assignRole('User');

        $this->actingAs($this->admin)->put(route('users.update', $user), array_merge($this->data, [
            'name' => Str::repeat('abcd', 75),
            'role' => $this->employeeRole->id,
        ]))->assertSessionHasErrors([
            'name' => 'The name field must not be greater than 255 characters.',
        ]);

        $this->assertNotEquals($user->fresh()->name, Str::repeat('abcd', 75));
    }

    public function test_a_valid_email_address_must_be_provided_when_updating_a_user()
    {
        $user = User::factory()->create();

        $userData = array_merge($this->data, ['email' => 'hallo']);

        $this->actingAs($this->admin)->put(route('users.update', $user), $userData)
            ->assertSessionHasErrors([
                'email' => 'The email field must be a valid email address.',
            ]);

        $this->assertNotEquals($user->fresh()->email, $userData['email']);
    }

    public function test_the_provided_email_address_cannot_be_longer_than_255_character_when_updating_a_user()
    {
        $user = User::factory()->create();

        $emailTooLong = Str::repeat('abc', 90).'@example.com';
        $userData = array_merge($this->data, ['email' => $emailTooLong]);

        $this->actingAs($this->admin)->put(route('users.update', $user), $userData)
            ->assertSessionHasErrors([
                'email' => 'The email field must not be greater than 255 characters.',
            ]);

        $this->assertNotEquals($user->fresh()->email, $userData['email']);
    }

    public function test_a_unique_email_address_must_be_provided_when_updating_a_user()
    {
        $user = User::factory()->create();
        User::factory()->create(['email' => 'john@example.com']);

        $this->actingAs($this->admin)->put(route('users.update', $user), array_merge($this->data, [
            'email' => 'john@example.com',
        ]))->assertSessionHasErrors([
            'email' => 'The email has already been taken.',
        ]);

        $this->assertNotEquals($user->fresh()->email, 'john@example.com');
    }

    public function test_an_existing_role_must_be_provided_when_updating_a_user()
    {
        $user = User::factory()->create()->assignRole('User');

        $this->actingAs($this->admin)->put(route('users.update', $user), array_merge($this->data, [
            'role' => 9999,
        ]))->assertSessionHasErrors([
            'role' => 'The selected role is invalid.',
        ]);

        $this->assertNotEquals($user->fresh()->roles()->first()->id, 9999);
    }

    public function test_a_guest_cannot_update_a_user()
    {
        $user = User::factory()->create()->assignRole('User');

        $this->put(route('users.update', $user), array_merge($this->data, [
            'role' => $this->employeeRole->id,
        ]))->assertRedirect(route('login'));

        $user->refresh();
        $this->assertNotEquals($user->name, $this->data['name']);
        $this->assertNotEquals($user->email, $this->data['email']);
        $this->assertNotEquals($user->roles()->first()->id, $this->employeeRole->id);
    }

    public function test_a_user_without_the_update_user_permission_cannot_update_a_user()
    {
        $user = User::factory()->create()->assignRole('User');

        $this->actingAs($this->user)->put(route('users.update', $user), array_merge($this->data, [
            'role' => $this->employeeRole->id,
        ]))->assertForbidden();

        $user->refresh();
        $this->assertNotEquals($user->name, $this->data['name']);
        $this->assertNotEquals($user->email, $this->data['email']);
        $this->assertNotEquals($user->roles()->first()->id, $this->employeeRole->id);
    }

    public function test_a_user_with_the_update_user_permission_may_update_a_user()
    {
        $user = User::factory()->create([
            'email' => 'david.smith@example.com',
        ])->assignRole('User');

        $this->actingAs($this->admin)->put(route('users.update', $user), array_merge($this->data, [
            'role' => $this->employeeRole->id,
        ]))->assertRedirect(route('users.index'))
            ->assertSessionHas([
                'success' => $user->fresh()->name.'\'s account has been updated!',
            ]);

        $user->refresh();
        $this->assertEquals($user->name, $this->data['name']);
        $this->assertEquals($user->email, $this->data['email']);
        $this->assertTrue($user->hasRole('Employee'));
        $this->assertFalse($user->hasRole('User'));
    }

    public function test_the_user_get_redirected_to_the_edit_user_page_when_the_invalid_email_exception_occurs_while_updating_a_user()
    {
        $user = User::factory()->create(['email' => 'john@example.com'])->assignRole('Employee');

        $this->actingAs($this->admin)->put(route('users.update', $user), array_merge($this->data, [
            'role' => $this->employeeRole->id,
        ]))
            ->assertRedirect(route('users.edit', $user))
            ->assertSessionHasErrors([
                'error' => 'The email does not match the original email address.',
            ]);
    }

    public function test_the_user_get_redirected_to_the_edit_user_page_when_the_unable_to_change_role_exception_occurs_while_updating_the_user()
    {
        $user = User::factory()->create(['email' => 'john@example.com'])->assignRole(['Super Admin']);

        $this->actingAs($this->admin)->put(route('users.update', $user), array_merge($this->data, [
            'email' => $user->email,
            'role' => $this->employeeRole->id,
        ]))
            ->assertRedirect(route('users.edit', $user))
            ->assertSessionHasErrors([
                'error' => 'Unable to change the role of this user.',
            ]);
    }

    public function test_a_guest_cannot_delete_a_user()
    {
        $user = User::factory()->create();
        $count = User::count();

        $this->delete(route('users.destroy', $user))
            ->assertRedirect(route('login'));

        $this->assertEquals($count, User::count());
    }

    public function test_a_user_without_the_delete_user_permission_cannot_delete_a_user()
    {
        $user = User::factory()->create();
        $count = User::count();

        $this->actingAs($this->user)->delete(route('users.destroy', $user))
            ->assertForbidden();

        $this->assertEquals($count, User::count());
    }

    public function test_a_user_with_the_delete_user_permission_can_delete_a_user()
    {
        $user = User::factory()->create();

        $this->actingAs($this->admin)->delete(route('users.destroy', $user))
            ->assertRedirect(route('users.index'))
            ->assertSessionHas([
                'success' => 'User has been deleted!',
            ]);

        $this->assertEquals($this->initialCount, User::count());
    }
}
