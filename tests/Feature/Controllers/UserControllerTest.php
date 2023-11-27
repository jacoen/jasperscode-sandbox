<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_a_guest_cannot_create_a_new_user_account()
    {
        $data = [
            'name' => 'Sample User',
            'email' => 'sample.user@example.com',
        ];

        $this->get(route('users.create'))->assertRedirect(route('login'));

        $this->post(route('users.store', $data))
            ->assertRedirect(route('login'));

        $this->assertDatabaseMissing('users', $data);
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

    public function test_all_fields_are_required_when_creating_a_user()
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
                'role' => 'The role field is required.',
            ]);

        $this->assertDatabaseMissing('users', [
            'name' => $data['name'],
            'email' => $data['email'],
        ]);
    }

    public function test_the_email_of_a_user_must_be_unique()
    {
        User::factory()->create(['name' => 'John Doe Senior', 'email' => 'john@example.com']);

        $data = [
            'name' => 'John Doe Junior',
            'email' => 'john@example.com',
            'role' => 4,
        ];

        $this->actingAs($this->admin)->post(route('users.store'), $data)
            ->assertSessionHasErrors([
                'email' => 'The email has already been taken.',
            ]);

        // 4 users from using the factory in testcase file
        $this->assertDatabaseCount('users', 5);

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
            'role' => 99,
        ];

        $this->actingAs($this->admin)->post(route('users.store'), $data)
            ->assertSessionHasErrors([
                'role' => 'The selected role is invalid.',
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
}
