<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Notifications\AccountCreatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $data;

    public function setUp(): void
    {
        parent::setUp();

        $this->data = [
            'name' => 'David Smith',
            'email' => 'david.smith@example.com',
        ];
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
        $this->twoFactorConfirmation($this->admin);

        $this->get(route('users.index'))
            ->assertOk()
            ->assertSeeText([
                $this->admin->name, $this->admin->email, 'Admin',
                $this->manager->name, $this->manager->email, 'Manager',
                $this->employee->name, $this->employee->email, 'Employee',
                $this->user->name, $this->user->email,
            ]);
    }

    public function test_a_guest_cannot_create_a_new_user_account()
    {
        $this->get(route('users.create'))->assertRedirect(route('login'));

        $this->post(route('users.store', $this->data))
            ->assertRedirect(route('login'));

        $this->assertDatabaseMissing('users', $this->data);
    }

    public function test_a_user_without_the_create_user_permission_cannot_create_a_new_user()
    {
        $this->actingAs($this->employee)->get(route('users.create'))
            ->assertForbidden();

        $this->actingAs($this->employee)->post(route('users.store'), $this->data)
            ->assertForbidden();

        $this->assertDatabaseCount('users', 4);
        $this->assertDatabaseMissing('users', $this->data);
    }

    public function test_the_name_and_email_fileds_are_required_when_creating_a_user()
    {
        $this->twoFactorConfirmation($this->admin);

        $data = [
            'name' => '',
            'email' => '',
        ];

        $this->post(route('users.store'), $data)
            ->assertSessionHasErrors([
                'name' => 'The name field is required.',
                'email' => 'The email field is required.',
            ]);

        $this->assertDatabaseMissing('users', [
            'name' => $data['name'],
            'email' => $data['email'],
        ]);
    }

    public function test_the_email_address_must_be_valid_when_creating_a_user_account()
    {
        $this->twoFactorConfirmation($this->admin);
        
        $userData = array_merge($this->data, ['email' => 'hallo']);

        $this->post(route('users.store'), $userData)
            ->assertSessionHasErrors([
                'email' => 'The email field must be a valid email address.',
            ]);

        $this->assertDatabaseMissing('users', $userData);
    }

    public function test_the_email_of_a_user_must_be_unique()
    {
        $this->twoFactorConfirmation($this->admin);

        User::factory()->create(['name' => 'John Doe Senior', 'email' => 'john@example.com']);

        $userData = array_merge($this->data, [
            'email' => 'john@example.com',
            'role' => 4,
        ]);

        $this->post(route('users.store'), $userData)
            ->assertSessionHasErrors([
                'email' => 'The email has already been taken.',
            ]);

        // 4 users from using the factory in testcase file
        $this->assertDatabaseCount('users', 5);

        $this->assertDatabaseMissing('users', [
            'name' => $userData['name'],
            'email' => $userData['email'],
        ]);
    }

    public function test_a_user_must_be_assigned_a_valid_role()
    {
        $this->twoFactorConfirmation($this->admin);

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

    public function test_a_user_with_the_create_user_permission_can_create_a_new_user()
    {
        $this->twoFactorConfirmation($this->admin);

        $userData = array_merge($this->data, ['role' => 4]);

        $this->get(route('users.create'))
            ->assertOk();

        $this->post(route('users.store'), $userData)
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success', 'A new user was created.');

        $this->assertDatabaseCount('users', 5);
        $this->assertDatabaseHas('users', [
            'name' => $userData['name'],
            'email' => $userData['email'],
        ]);
    }

    public function test_when_a_user_gets_created_without_a_role_this_user_gets_assigned_a_default_role()
    {
        $this->twoFactorConfirmation($this->admin);

        $data = [
            'name' => 'Test User',
            'email' => 'test.user@example.com',
        ];

        $this->post(route('users.store'), $data)
            ->assertRedirect(route('users.index'));

        $user = User::where('email', 'test.user@example.com')->first();

        $this->assertTrue($user->hasRole('User'));
    }

    public function test_a_user_that_gets_created_with_a_role_does_get_the_default_role_assigned()
    {
        $this->twoFactorConfirmation($this->admin);

        $userData = array_merge($this->data, [
            'role' => 4, //Employee
        ]);

        $this->post(route('users.store'), $userData)
            ->assertRedirect(route('users.index'));

        $user = User::where('email', $userData['email'])->first();

        $this->assertTrue($user->hasRole('Employee'));
    }

    public function test_when_a_new_user_has_been_created_a_password_token_will_be_generated_for_this_user()
    {
        $this->twoFactorConfirmation($this->admin);

        $this->post(route('users.store'), $this->data)
            ->assertRedirect(route('users.index'));

        $user = User::where('email', $this->data['email'])->first();

        $this->assertNotEmpty($user->password_token);
    }

    public function test_when_a_new_user_has_been_created_this_user_receives_a_notification()
    {
        $this->twoFactorConfirmation($this->admin);

        $userData = array_merge($this->data, ['role' => 4]);

        Notification::fake();

        $this->post(route('users.store'), $userData)
            ->assertRedirect(route('users.index'));

        $user = User::where('email', $userData['email'])->first();

        $this->assertEquals($user->email, $userData['email']);
        Notification::assertSentTo($user, AccountCreatedNotification::class);
    }

    public function test_a_guest_cannot_edit_an_existing_user()
    {
        $user = User::factory()->create();

        $this->get(route('users.edit', $user))
            ->assertRedirect(route('login'));

        $this->put(route('users.update', $user), $this->data)
            ->assertRedirect(route('login'));

        $this->assertDatabaseMissing('users', array_merge($this->data, ['id' => $user->id]));
    }

    public function test_a_user_without_the_update_user_permission_cannout_edit_an_existing_user()
    {
        $user = User::factory()->create();

        $userData = array_merge($this->data, ['role' => 4]);

        $this->actingAs($this->employee)->get(route('users.edit', $user))
            ->assertForbidden();

        $this->actingAs($this->employee)->put(route('users.update', $user), $userData)
            ->assertForbidden();
    }

    public function test_all_the_fields_are_required_when_editing_an_existing_user()
    {
        $this->twoFactorConfirmation($this->admin);

        $user = User::factory()->create();

        $data = [
            'name' => '',
            'email' => '',
            'role' => '',
        ];

        $this->put(route('users.update', $user), $data)
            ->assertSessionHasErrors([
                'name' => 'The name field is required.',
                'email' => 'The email field is required.',
                'role' => 'The role field is required.',
            ]);

        $this->assertNotEquals($user->fresh()->name, $data['name']);
        $this->assertNotEquals($user->fresh()->email, $data['email']);
    }

    public function test_the_email_address_must_be_valid_whe_editing_a_user_account()
    {
        $this->twoFactorConfirmation($this->admin);

        $user = User::factory()->create();

        $userData = array_merge($this->data, [
            'email' => 'hallo',
            'role' => 4,
        ]);

        $this->put(route('users.update', $user), $userData)
            ->assertSessionHasErrors(['email' => 'The email field must be a valid email address.']);

        $this->assertDatabaseMissing('users', ['id' => $user->id, 'email' => $userData['email']]);
    }

    public function test_a_user_with_the_update_user_permission_cannot_change_the_email_address_of_an_existing_user()
    {
        $this->twoFactorConfirmation($this->admin);

        $user = User::factory()->create(['email' => 'Scott.price@example.com'])->assignRole('Employee');

        $userData = array_merge($this->data, [
            'name' => $user->name,
            'email' => 'David.Smith@example.com',
            'role' => 4,
        ]);

        $this->put(route('users.update', $user), $userData)
            ->assertSessionHasErrors([
                'email' => 'The email does not match the original email address',
            ]);

        $this->assertNotEquals($user->fresh()->email, $userData['email']);

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $userData['email'],
        ]);
    }

    public function test_a_user_with_the_update_user_permission_can_edit_an_existing_user()
    {
        $this->twoFactorConfirmation($this->admin);

        $user = User::factory()->create()->assignRole('Employee');

        $userData = [
            'name' => 'Roger Davids',
            'email' => $user->email,
            'role' => 4, // Employee
        ];

        $this->get(route('users.edit', $user))
            ->assertOk();

        $this->put(route('users.update', $user), $userData)
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => $userData['name'],
            'email' => $userData['email'],
        ]);

        $this->assertTrue($user->fresh()->hasRole('Employee'));
        $this->assertFalse($user->fresh()->hasRole('User'));
    }

    public function test_a_guest_cannot_delete_a_user_account()
    {
        $user = User::factory()->create();

        $this->delete(route('users.destroy', $user))
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => $user->email]);
    }

    public function test_a_user_without_the_delete_user_permission_cannot_delete_a_user_account()
    {
        $user = User::factory()->create();

        $this->actingAs($this->manager)->delete(route('users.destroy', $user))
            ->assertForbidden();
    }

    public function a_user_with_the_delete_user_permission_can_delete_a_user_account()
    {
        $user = User::factory()->create();

        $this->twoFactorConfirmation($this->admin);

        $this->post(route('verify.store'), [
            'two_factor_code' => $this->admin->two_factor_code
        ]);

        $this->delete(route('users.destroy', $user))
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
            'email' => $user->email,
        ]);
    }

    protected function twoFactorConfirmation($user)
    {
        $this->actingAs($user);
        $user = $user->fresh();

        $user->generateTwoFactorCode();

        $this->post(route('verify.store'), [
            'two_factor_code' => $user->two_factor_code
        ]);
    }
}
