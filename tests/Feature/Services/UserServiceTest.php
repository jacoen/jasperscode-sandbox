<?php

namespace Tests\Feature\Services;

use App\Events\RoleUpdatedEvent;
use App\Exceptions\InvalidEmailException;
use App\Exceptions\UnableToChangeRoleException;
use App\Listeners\RoleUpdatedListener;
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

    protected UserService $userService;

    protected array $data;

    public function setUp(): void
    {
        parent::setUp();

        $this->userService = app(UserService::class);

        Notification::fake();
        Event::fake();

        $this->data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ];
    }

    public function test_it_can_create_a_user_account()
    {
        $user = $this->userService->store($this->data);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($user->name, $this->data['name']);
        $this->assertEquals($user->email, $this->data['email']);
        $this->assertNotEmpty($user->password);

        $this->assertDatabaseHas('users', $this->data);
    }

    public function test_it_generates_a_hashed_password_for_a_user_account()
    {
        $user = $this->userService->store($this->data);

        $this->assertStringStartsWith('$2y$', $user->password);
    }

    public function test_it_generates_a_password_token_after_the_user_has_been_created()
    {
        $user = $this->userService->store($this->data);

        $this->assertNotEmpty($user->password_token);
        $this->assertEqualsWithDelta($user->token_expires_at, now()->addHour(), 2);
    }

    public function test_it_assign_a_default_role_to_the_user_when_no_user_role_has_been_provided_while_creating_a_user_account()
    {
        $user = $this->userService->store($this->data);

        $this->assertEquals($user->roles()->first()->name, 'User');
    }

    public function test_it_assigns_the_selected_role_to_the_user_after_the_user_has_been_created()
    {
        $role = Role::where('name', 'Employee')->first();

        $user = $this->userService->store(array_merge($this->data, [
            'role' => $role->id,
        ]));

        $this->assertEquals($user->roles()->first()->name, 'Employee');
    }

    public function test_it_fires_the_role_updated_event_after_the_user_has_a_role_assigned_to_them()
    {
        $user = $this->userService->store($this->data);

        Event::assertDispatched(RoleUpdatedEvent::class, function ($event) use ($user) {
            return $event->user->id === $user->id;
        });

        Event::assertListening(RoleUpdatedEvent::class, RoleUpdatedListener::class);
    }

    public function test_it_send_a_notification_to_the_the_user_after_their_account_has_been_created()
    {
        $user = $this->userService->store($this->data);

        Notification::assertSentTo($user, AccountCreatedNotification::class);
    }

    public function test_it_can_update_a_user()
    {
        $role = Role::where('name', 'Manager')->first();
        $user = User::factory()->create(['name' => 'Jane coburn', 'email' => 'jane@example.com'])->assignRole('Employee');

        $data = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ];

        $user = $this->userService->update($user, array_merge($data, ['role' => $role->id]));

        $this->assertEquals($user->name, $data['name']);
        $this->assertTrue($user->hasRole('Manager'));

        $this->assertDatabaseHas('users', array_merge($data, ['id' => $user->id]));
    }

    public function test_it_throws_an_exception_when_a_different_email_address_is_provided_when_editing_a_user()
    {
        $role = Role::where('name', 'Employee')->first();
        $user = User::factory()->create(['email' => 'john@example.com'])->assignRole($role);

        $data = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ];

        $this->expectException(InvalidEmailException::class);
        $this->expectExceptionMessage('The email does not match the original email address');
        $user = $this->userService->update($user, array_merge($data, ['role' => $role->id]));
    }

    public function test_it_throws_an_exception_when_a_different_role_has_been_provided_when_editing_the_super_admin()
    {
        $role = Role::where('name', 'Super Admin')->first();
        $employee = Role::where('name', 'Employee')->first();
        $user = User::factory()->create(['email' => 'john@example.com'])->assignRole($role);

        $this->expectException(UnableToChangeRoleException::class);
        $user = $this->userService->update($user, array_merge($this->data, ['role' => $employee->id]));
    }

    public function test_it_gets_users_by_roles()
    {
        User::factory(4)->create()->each(function ($user) {
            $user->assignRole('Employee');
        });
        $users = $this->userService->getUsersByRoles(['Employee', 'Manager']);

        $this->assertCount(6, $users);
        $this->assertArrayHasKey($this->employee->id, $users);
        $this->assertEquals($this->employee->name, $users[$this->employee->id]);
        $this->assertArrayHasKey($this->manager->id, $users);
        $this->assertEquals($this->manager->name, $users[$this->manager->id]);
        $this->assertArrayNotHasKey($this->admin->id, $users);
    }
}
