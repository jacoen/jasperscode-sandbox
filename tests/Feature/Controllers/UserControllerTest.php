<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Notifications\AccountActivatedNotification;
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
        $data = [
            'name' => '',
            'email' => '',
        ];

        $this->actingAs($this->admin)->post(route('users.store'), $data)
            ->assertSessionHasErrors([
                'name' => 'The name field is required.',
                'email' => 'The email field is required.',
            ]);

        $this->assertDatabaseMissing('users', [
            'name' => $data['name'],
            'email' => $data['email'],
        ]);
    }

    public function test_the_email_of_a_user_must_be_unique()
    {
        User::factory()->create(['name' => 'John Doe Senior', 'email' => 'john@example.com']);

        $userData = array_merge($this->data, [
            'email' => 'john@example.com',
            'role' => 4,
        ]);

        $this->actingAs($this->admin)->post(route('users.store'), $userData)
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
        $userData = array_merge($this->data, ['role' => 99]);

        $this->actingAs($this->admin)->post(route('users.store'), $userData)
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
        $userData = array_merge($this->data, ['role' => 4]);

        $this->actingAs($this->admin)->get(route('users.create'))
            ->assertOk();

        $this->actingAs($this->admin)->post(route('users.store'), $userData)
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
        $data = [
            'name' => 'Test User',
            'email' => 'test.user@example.com',
        ];

        $this->actingAs($this->admin)->post(route('users.store'), $data)
            ->assertRedirect(route('users.index'));

        $user = User::where('email', 'test.user@example.com')->first();

        $this->assertTrue($user->hasRole('User'));
    }

    public function test_a_user_that_gets_created_with_a_role_does_get_the_default_role_assigned()
    {
        $data = [
            'name' => 'Test User',
            'email' => 'test.user@example.com',
            'role' => 4 //Employee
        ];

        $this->actingAs($this->admin)->post(route('users.store'), $data)
            ->assertRedirect(route('users.index'));

        $user = User::where('email', $data['email'])->first();

        $this->assertTrue($user->hasRole('Employee'));
    }

    public function test_when_a_new_user_has_been_created_this_user_receives_a_notification()
    {
        $userData = array_merge($this->data, ['role' => 4]);

        Notification::fake();

        $this->actingAs($this->admin)->post(route('users.store'), $userData)
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
}
