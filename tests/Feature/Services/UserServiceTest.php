<?php

namespace Tests\Feature\Services;

use App\Events\RoleUpdatedEvent;
use App\Exceptions\InvalidEmailException;
use App\Listeners\RoleUpdatedListener;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\AccountCreatedNotification;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $userService;

    public function setUp(): void
    {
        parent::setUp();

        $this->userService = app(UserService::class);

        Notification::fake();
        Event::fake();
    }

    public function test_it_can_create_a_user_account()
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ];

        $user = $this->userService->store($data);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($user->name, $data['name']);
        $this->assertEquals($user->email, $data['email']);
        $this->assertNotEmpty($user->password);

        $this->assertDatabaseHas('users', $data);
    }

    public function test_it_generates_a_hashed_password_for_a_user_account()
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ];

        $user = $this->userService->store($data);

        $this->assertStringStartsWith('$2y$', $user->password);
    }

    public function test_it_generates_a_password_token_after_the_user_has_been_created()
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ];

        $user = $this->userService->store($data);

        $this->assertNotEmpty($user->password_token);
        $this->assertEqualsWithDelta($user->token_expires_at, now()->addHour(), 2);
    }

    public function test_it_assign_a_default_role_to_the_user_when_no_user_role_has_been_selected_while_creating_a_user_account()
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ];

        $user = $this->userService->store($data);

        $this->assertEquals($user->roles()->first()->name, 'User');
    }

    public function test_it_assigns_the_selected_role_to_the_user_after_the_user_has_been_created()
    {
        $role = Role::where('name', 'Employee')->first();

        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => $role->id,
        ];

        $user = $this->userService->store($data);

        $this->assertEquals($user->roles()->first()->name, 'Employee');
    }

    public function test_it_fires_the_role_updated_event_after_the_user_has_a_role_assigned_to_them()
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ];

        $user = $this->userService->store($data);

        Event::assertDispatched(RoleUpdatedEvent::class, function ($event) use ($user) {
            return $event->user->id === $user->id;
        });

        Event::assertListening(RoleUpdatedEvent::class, RoleUpdatedListener::class);
    }

    public function test_it_send_a_notification_the_the_user_after_their_account_has_been_created()
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ];

        $user = $this->userService->store($data);

        Notification::assertSentTo($user, AccountCreatedNotification::class);
    }

    public function test_it_throws_an_exception_when_a_different_email_address_is_provided_when_editing_a_user()
    {
        $role = Role::where('name', 'Employee')->first();
        $user = User::factory()->create(['email' => 'john@example.com'])->assignRole($role);

        $data =  [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ];

        $this->expectException(InvalidEmailException::class);
        $this->expectExceptionMessage('The email does not match the original email address');
        $user = $this->userService->update($user, $data, ['role' => $role->id]);
    }

    public function test_it_can_update_a_user()
    {
        $role = Role::where('name', 'Manager')->first();
        $user = User::factory()->create(['name' => 'Jane coburn','email' => 'jane@example.com'])->assignRole('Employee');

        $data =  [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ];

        $user = $this->userService->update($user, array_merge($data, ['role' => $role->id]));

        $this->assertEquals($user->name, $data['name']);
        $this->assertTrue($user->hasRole('Manager'));

        $this->assertDatabaseHas('users', array_merge($data, ['id' => $user->id]));
    }

    public function test_it_can_delete_a_user()
    {
        $user = User::factory()->create()->assignRole('employee');

        $this->userService->delete($user);

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);

        $this->assertDatabaseHas('users', ['id' => $this->employee->id]);
    }

    public function test_it_unassigns_the_related_project_when_deleting_a_user()
    {
        $user = User::factory()->create()->assignRole('manager');
        $project = Project::factory()->create(['manager_id' => $user->id]);

        $this->userService->delete($user);

        $this->assertNull($project->fresh()->manager_id);
    }

    public function test_it_deletes_a_task_when_deleting_the_user_who_is_the_author()
    {
        $user = User::factory()->create()->assignRole('manager');
        $task = Task::factory()->create(['author_id' => $user->id]);
        
        $this->userService->delete($user);

        $this->assertDatabaseMissing('tasks', ['id' => $task->id, 'author_id' => $task->author_id]);
    }

    public function test_it_unassigns_the_related_task_when_deleting_the_user()
    {
        $user = User::factory()->create()->assignRole('manager');
        $task = Task::factory()->create(['user_id' => $user->id]);
        
        $this->userService->delete($user);

        $this->assertNull($task->fresh()->user_id);
    }


}
