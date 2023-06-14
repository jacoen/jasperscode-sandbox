<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\AccountCreatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_visit_the_user_overview_page()
    {
        $this->get(route('users.index'))->assertRedirect(route('login'));
    }

    public function test_only_a_user_with_the_read_user_permission_can_view_the_user_overview_page()
    {
        $this->actingAs($this->employee)->get(route('users.index'))
            ->assertForbidden();

        $this->actingAs($this->admin)->get(route('users.index'))
            ->assertOk()
            ->assertSeeText([$this->admin->name, $this->admin->email, 'Admin'])
            ->assertSeeText([$this->employee->name, $this->employee->email, 'Employee']);
    }

    public function test_a_user_without_the_create_user_permission_cannot_create_a_new_user()
    {
        $data = [
            'name' => 'Sample User',
            'email' => 'sample.user@example.com',
        ];

        $this->actingAs($this->employee)->get(route('users.create'))
            ->assertForbidden();

        $this->actingAs($this->employee)->post(route('users.store'), $data)
            ->assertForbidden();

        $this->assertDatabaseCount('users', 4);
        $this->assertDatabaseMissing('users', $data);
    }

    public function test_fields_are_required()
    {   
        $data = [
            'name' => '',
            'email' => '',
            'role' => '',
        ];

        $this->actingAs($this->admin)->post(route('users.store'), $data)
            ->assertSessionHasErrors([
                'name' => 'The name field is required.',
                'email' => 'The email field is required.',
                'role' => 'The role field is required.'
            ]);

        $this->assertDatabaseMissing('users', [
            'name' => $data['name'],
            'email' => $data['email'],
        ]);
    }

    public function test_the_email_of_a_user_must_be_unique()
    {
        $user = User::factory()->create(['name' => 'John Doe Senior', 'email' => 'john@example.com']);

        $data = [
            'name' => 'John Doe Junior',
            'email' => 'john@example.com',
            'role' => 4
        ];

        $this->actingAs($this->admin)->post(route('users.store'), $data)
            ->assertSessionHasErrors([
                'email' => 'The email has already been taken.',
            ]);

        $this->assertDatabaseMissing('users', [
            'name' => $data['name'],
            'email' => $data['email'],
        ]);
    }

    public function test_a_user_must_be_assigned_a_valid_role()
    {
        $data = [
            'name' => 'New User',
            'email' => 'new.user@example.com',
            'role' => 99
        ];

        $this->actingAs($this->admin)->post(route('users.store'), $data)
            ->assertSessionHasErrors([
                'role' => 'The selected role is invalid.'
            ]);

        $this->assertDatabaseMissing('users', [
            'name' => $data['name'],
            'email' => $data['email'],
        ]);
    }

    public function test_a_user_with_the_create_user_permission_can_create_a_new_user()
    {
        $data = [
            'name' => 'Sample User',
            'email' => 'sample.user@example.com',
            'role' => 4,
        ];

        $this->actingAs($this->admin)->get(route('users.create'))
            ->assertOk();

        $this->actingAs($this->admin)->post(route('users.store'), $data)
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success', 'A new user was created.');

        $this->assertDatabaseCount('users', 5);
        $this->assertDatabaseHas('users', [
            'name' => $data['name'],
            'email' => $data['email'],
        ]);
    }

    public function test_when_a_new_user_has_been_created_a_message_gets_sent()
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => 4,
        ];

        Notification::fake();

        
        $this->actingAs($this->admin)->post(route('users.store'), $data)
            ->assertRedirect(route('users.index'));

        $user = User::where('email', $data['email'])->get();
        
        Notification::assertSentTo($user, AccountCreatedNotification::class);
    }

    public function test_a_user_without_the_edit_user_permission_cannot_edit_a_user()
    {
        $user = User::factory()->create();

        $this->actingAs($this->employee)->get(route('users.edit', $user))
            ->assertForbidden();

        $data = [
            'name' => 'Bob Brouwer',
            'email' => 'bob.brouwer@example.com',
            'role' => $user->role,
        ];

        $this->actingAs($this->employee)->put(route('users.update', $user), $data)
            ->assertForbidden();

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
            'name' => $data['name'],
            'email' => $data['email'],
        ]);
    }

    public function test_a_user_with_the_edit_user_permission_can_edit_a_user()
    {
        $user = User::factory()->create();

        $this->actingAs($this->admin)->get(route('users.edit', $user))
            ->assertOk()
            ->assertSee([$user->name, $user->email, 'User']);

        $data = [
            'name' => 'Bob Brouwer',
            'email' => 'bob.brouwer@example.com',
            'role' => $user->roles->first()->id,
        ];

        $this->actingAs($this->admin)->put(route('users.update', $user), $data)
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success', $data['name'].'\'s account has been updated!');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => $data['name'],
            'email' => $data['email']
        ]);
    }

    public function test_a_user_without_the_delete_user_permission_cannot_delete_a_user()
    {
        $user = User::factory()->create();

        $this->actingAs($this->employee)->delete(route('users.destroy', $user))
            ->assertForbidden();

        $this->assertDatabaseHas('users', [
            'name' => $user->name,
            'email' => $user->email
        ]);
    }
}
