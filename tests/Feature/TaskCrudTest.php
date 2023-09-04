<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TaskCrudTest extends TestCase
{
    use RefreshDatabase;

    protected $project;

    public function setUp(): void 
    {
        parent::setUp();

        $this->project = Project::factory()->create(['manager_id' => $this->manager->id]);
    }

    public function test_a_guest_cannot_visit_the_task_overview_page()
    {
        $this->get(route('tasks.index'))
            ->assertRedirect(route('login'));
    }

    public function test_a_user_without_the_read_task_permission_cannot_visit_the_tasks_overview_page()
    {
        $this->actingAs($this->user)->get(route('tasks.index'))
            ->assertForbidden();
    }

    public function test_a_user_without_at_least_the_admin_role_can_only_see_the_tasks_that_are_assigned_to_them()
    {
        $unassignedTask = Task::factory()->for($this->project)->create();
        $adminTask = Task::factory()->for($this->project)->create(['user_id' => $this->admin->id]);
        
        $this->actingAs($this->employee)->get(route('tasks.user'))
            ->assertOk()
            ->assertSeeText($this->employee->name.'\'s tasks')
            ->assertSeeText('No tasks yet.');
        
        $assignedTask = Task::factory()->for($this->project)->create(['user_id' => $this->employee->id]);

        $this->actingAs($this->employee)->get(route('tasks.user'))
            ->assertOk()
            ->assertSeeText(Str::limit($assignedTask->title, 25))
            ->assertDontSeeText([$unassignedTask->title, $adminTask->title]);
    }


    public function test_an_admin_can_see_an_overview_of_all_tasks()
    {
        $unassignedTask = Task::factory()->for($this->project)->create();
        $adminTask = Task::factory()->for($this->project)->create(['user_id' => $this->admin->id]);
        $employeeTask = Task::factory()->for($this->project)->create(['user_id' => $this->employee->id]);

        $this->actingAs($this->admin)->get(route('tasks.index'))
            ->assertOk()
            ->assertSeeText([
                Str::limit($unassignedTask->title, 25),
                Str::limit($adminTask->title, 25),
                Str::limit($employeeTask->title, 25),
            ]);
    }

    public function test_a_guest_cannot_create_a_new_task()
    {
        $this->get(route('tasks.create', $this->project))
            ->assertRedirect('login');
    }

    public function test_a_user_without_the_create_task_permission_cannot_create_a_new_task()
    {
        $data = [
            'title' => 'some title',
            'description' => 'some description',
            'user_id' => null,
        ];

        $this->actingAs($this->user)->get(route('tasks.create', $this->project))
            ->assertForbidden();

        $this->actingAs($this->user)->post(route('tasks.store', $this->project), $data)
            ->assertForbidden();

        $this->assertDatabaseMissing('tasks', $data);
    }

    public function test_a_user_with_the_create_task_permission_can_create_a_new_task()
    {
        $data = [
            'title' => 'some title',
            'description' => 'some description',
            'user_id' => null,
        ];

        $this->actingAs($this->employee)->get(route('tasks.create', $this->project))
            ->assertOk();

        $this->actingAs($this->employee)->post(route('tasks.store', $this->project), $data)
            ->assertRedirect(route('projects.show', $this->project))
            ->assertSessionHas('success', 'A new task has been created.');

        $this->actingAs($this->employee)->get(route('projects.show', $this->project))
            ->assertSeeText($data['title']);

        $this->assertDatabaseHas('tasks', $data);
    }

    public function test_a_task_cannot_be_created_on_a_trashed_project()
    {
        $trashedProject = Project::factory()->trashed()->create();

        $data = [
            'title' => 'a title for a task of the trashed project',
            'description' => 'A sample description',
            'user_id' => null,
        ];

        $this->actingAs($this->manager)->get(route('tasks.create', $trashedProject))
            ->assertNotFound();

        $this->actingAs($this->manager)->post(route('tasks.store', $trashedProject), $data)
            ->assertNotFound();

        $this->assertDatabaseMissing('tasks', $data);
    }

    public function test_a_task_can_only_be_created_for_a_valid_project()
    {
        $closedProject = Project::factory()->create(['status' => 'closed']);

        $this->actingAs($this->manager)->get(route('tasks.create', $closedProject))
            ->assertRedirect(route('projects.show', $closedProject))
            ->assertSessionHasErrors(['error' => 'Cannot create a task when the project is not open or pending.']);

        $this->actingAs($this->manager)->get(route('tasks.create', $this->project))
            ->assertOk();

        $data = [
            'title' => 'Some title here',
            'description' => 'A sample description',
            'user_id' => null,
        ];

        $closedData = array_merge($data, ['title' => 'title for a task in a closed project']);

        $this->actingAs($this->manager)->post(route('tasks.store', $closedProject), $closedData)
            ->assertRedirect(route('projects.show', $closedProject))
            ->assertSessionHasErrors(['error' => 'Cannot create a task when the project is not open or pending.']);

        $this->actingAs($this->manager)->post(route('tasks.store', $this->project), $data)
            ->assertRedirect(route('projects.show', $this->project))
            ->assertSessionHas('success', 'A new task has been created.');

        $this->assertDatabaseMissing('tasks', $closedData);
        $this->assertDatabaseHas('tasks', [
            'title' => $data['title'],
            'project_id' => $this->project->id,
        ]);
    }

    public function test_the_title_is_required_when_creating_a_new_task()
    {
        $data = [
            'title' => '',
            'description' => 'Some description here',
            'user_id' => null,
        ];

        $this->actingAs($this->employee)->post(route('tasks.store', $this->project), $data)
            ->assertSessionHasErrors([
                'title' => 'The title field is required.',
            ]);
        
        $this->assertDatabaseMissing('tasks', $data);
    }

    public function test_only_a_valid_user_can_be_assigned_to_a_task()
    {
        $user = User::factory()->create();
        
        $data = [
            'title' => 'Here is a title',
            'description' => 'Some description here',
        ];

        $invalidUser = array_merge($data, ['user_id' => 99]);
        $validUser = array_merge($data, ['user_id' => $user->id]);

        $this->actingAs($this->employee)->post(route('tasks.store', $this->project), $invalidUser)
            ->assertSessionHasErrors(['user_id' => 'The selected user is invalid.']);

        $this->actingAs($this->employee)->post(route('tasks.store', $this->project), $validUser)
            ->assertRedirect(route('projects.show', $this->project))
            ->assertSessionHas('success', 'A new task has been created.');

        $this->assertDatabaseMissing('tasks', ['title' => $data['title'], 'user_id' => 99]);
        $this->assertDatabaseHas('tasks', ['title' => $data['title'], 'user_id' => $user->id]);
    }

    public function test_a_guest_cannot_visit_the_task_detail_page()
    {
        $task = Task::factory()->for($this->project)->create();

        $this->get(route('tasks.show', $task))
            ->assertRedirect(route('login'));
    }

    public function test_a_user_without_the_read_task_permission_cannot_visit_the_task_detail_page()
    {
        $task = Task::factory()->for($this->project)->create();

        $this->actingAs($this->user)->get(route('tasks.show', $task))
            ->assertForbidden();
    }

    public function test_a_user_with_the_read_task_permission_cannot_visit_the_detail_page_of_a_trashed_task()
    {
        $task = Task::factory()->for($this->project)->trashed()->create();

        $this->actingAs($this->employee)->get(route('tasks.show', $task))
            ->assertNotFound();
    }

    public function test_a_user_with_the_read_task_permission_can_visit_the_detail_page_of_a_task()
    {
        $task = Task::factory()->for($this->project)->create();

        $this->actingAs($this->employee)->get(route('tasks.show', $task))
            ->assertOk()
            ->assertSeeText([$task->title, $task->description]);
    }

    public function test_a_user_without_the_edit_task_permission_cannot_edit_a_task()
    {
        $task = Task::factory()->for($this->project)->create();

        $this->actingAs($this->user)->get(route('tasks.edit', $task))
            ->assertForbidden();

        $data = [
            'title' => 'Updated title',
            'user_id' => $this->employee->id,
            'status' => $task->status,
        ];

        $taskData = array_merge($data, ['id' => $task->id, 'author_id' => $task->author_id, 'project_id' => $task->project_id]);

        $this->actingAs($this->user)->put(route('tasks.update', $task), $taskData)
            ->assertForbidden();

        $this->assertDatabaseMissing('tasks', $taskData);

    }

    public function test_a_user_with_the_edit_task_permission_cannot_edit_a_trashed_task()
    {
        $task = Task::factory()->for($this->project)->trashed()->create();
        
        $this->actingAs($this->employee)->get(route('tasks.edit', $task))
            ->assertNotFound();

        $data = [
            'title' => 'Updated title',
            'user_id' => $this->employee->id,
            'status' => $task->status,
        ];

        $taskData = array_merge($data, ['id' => $task->id, 'author_id' => $task->author_id, 'project_id' => $task->project_id]);

        $this->actingAs($this->user)->put(route('tasks.update', $task), $taskData)
            ->assertNotFound();
    }

    public function test_a_user_with_the_edit_task_permission_can_edit_an_existing_task()
    {
        $task = Task::factory()->for($this->project)->create();

        $this->actingAs($this->employee)->get(route('tasks.edit', $task))
            ->assertOk();

        $data = [
            'title' => 'Updated title',
            'user_id' => $this->employee->id,
            'status' => 'pending',
        ];

        $taskData = array_merge($data, ['id' => $task->id, 'author_id' => $task->author_id, 'project_id' => $task->project_id]);

        $this->actingAs($this->employee)->put(route('tasks.update', $task), $taskData)
            ->assertRedirect(route('tasks.show', $task))
            ->assertSessionHas('success', 'The task '.$task->fresh()->title  .' has been updated.');

        $this->assertDatabaseHas('tasks', $taskData);
    }

    public function test_the_title_and_status_field_a_required_when_editing_a_task()
    {
        $task = Task::factory()->for($this->project)->create();

        $this->actingAs($this->employee)->get(route('tasks.edit', $task))
            ->assertOk();

        $data = [
            'title' => '',
            'user_id' => $this->employee->id,
            'status' => '',
        ];

        $taskData = array_merge($data, ['id' => $task->id, 'author_id' => $task->author_id, 'project_id' => $task->project_id]);

        $this->actingAs($this->employee)->put(route('tasks.update', $task), $taskData)
            ->assertSessionHasErrors([
                'title' => 'The title field is required.',
                'status' => 'The status field is required.',
            ]);

        $this->assertDatabaseMissing('tasks', $taskData);
    }

    public function test_the_status_field_must_contain_a_valid_value_when_editing_a_task()
    {
        $task = Task::factory()->for($this->project)->create(['status' => 'restored']);

        $data = array_merge($task->toArray(), [
            'title' => 'Some updated title',
            'user_id' => $this->employee->id,
        ]);

        $this->actingAs($this->employee)->put(route('tasks.update', $task), $data)
            ->assertSessionHasErrors(['status' => 'The selected status is invalid.']);

        $this->assertNotEquals($task, $data);
        $this->assertDatabaseMissing('tasks', $data);
    }

    public function test_a_guest_cannot_delete_a_task()
    {
        $task = Task::factory()->for($this->project)->create();

        $this->delete(route('tasks.destroy', $task))->assertRedirect(route('login'));

        $this->assertNotSoftDeleted($task);
    }

    public function test_a_user_without_the_delete_task_permission_cannot_delete_a_task()
    {
        $task = Task::factory()->for($this->project)->create();

        $this->actingAs($this->user)->delete(route('tasks.destroy', $task))
            ->assertForbidden();

        $this->assertNotSoftDeleted($task);
    }

    public function test_a_user_with_the_delete_task_permission_can_delete_a_task()
    {
        $task = Task::factory()->for($this->project)->create([
            'status' => 'pending', 
            'user_id' => $this->employee->id
        ]);

        $this->actingAs($this->employee)->delete(route('tasks.destroy', $task))
            ->assertRedirect(route('projects.show', $task->project))
            ->assertSessionHas('success', 'The task '.$task->title.' has been deleted.');

        $this->assertSoftDeleted($task);
        $this->assertTrue($task->fresh()->status == 'closed');
        $this->assertNull($task->fresh()->user_id);
    }

    public function test_when_an_assigned_task_gets_deleted_the_task_gets_unassigned()
    {
        $task = Task::factory()->for($this->project)->create(['user_id' => $this->employee->id]);

        $this->actingAs($this->employee)->delete(route('tasks.destroy', $task))
            ->assertRedirect(route('projects.show', $task->project))
            ->assertSessionHas('success', 'The task '.$task->title.' has been deleted.');
    }

    public function test_a_guest_cannot_restore_a_task()
    {
        $task = Task::factory()->for($this->project)->trashed()->create();

        $this->patch(route('tasks.restore', $task))
            ->assertRedirect(route('login'));

        $this->assertSoftDeleted($task);
    }

    public function test_a_user_without_the_restore_task_permission_cannot_restore_a_task()
    {
        $task = Task::factory()->for($this->project)->trashed()->create();

        $this->actingAs($this->employee)->patch(route('tasks.restore', $task))
            ->assertForbidden();

        $this->assertSoftDeleted($task);
    }

    public function test_a_task_cannot_be_restored_when_the_related_project_has_been_deleted()
    {
        $trashedProject = Project::factory()->trashed()->create();
        $task = Task::factory()->for($trashedProject)->trashed()->create();

        $this->actingAs($this->manager)->patch(route('tasks.restore', $task))
            ->assertRedirect(route('tasks.trashed'))
            ->assertSessionHasErrors(['error' => 'Could not restore task because the project has been deleted.']);

        $this->assertSoftDeleted($task);
    }

    public function test_a_task_cannot_be_restored_when_the_related_project_is_closed_or_completed()
    {
        $closedProject = Project::factory()->create(['status' => 'closed']);
        $task = Task::factory()->for($closedProject)->trashed()->create();

        $this->actingAs($this->manager)->patch(route('tasks.restore', $task))
            ->assertRedirect(route('tasks.trashed'))
            ->assertSessionHasErrors(['error' => 'Could not restore task becaues the project is either closed or completed.']);

        $this->assertSoftDeleted($task);
    }

    public function test_a_user_with_the_restore_task_permission_can_restore_a_task()
    {
        $task = Task::factory()->for($this->project)->trashed()->create();

        $this->actingAs($this->admin)->patch(route('tasks.restore', $task))
            ->assertRedirect(route('tasks.trashed'))
            ->assertSessionHas('success', 'The task '.$task->title. 'has been restored.');

        $this->assertNotSoftDeleted($task);
    }

    public function test_an_employee_can_filter_all_assinged_tasks_on_the_status()
    {
        $openTask = Task::factory()->for($this->project)->create(['status' => 'open', 'user_id' => $this->employee->id]);
        $pendingTask = Task::factory()->for($this->project)->create(['status' => 'pending', 'user_id' => $this->employee->id]);
        $closedTask = Task::factory()->for($this->project)->create(['status' => 'closed', 'user_id' => $this->employee->id]);
        $completedTask = Task::factory()->for($this->project)->create(['status' => 'completed', 'user_id' => $this->employee->id]);

        $this->actingAs($this->employee)->get(route('tasks.user'))
            ->assertOk()
            ->assertSeeText([
                Str::limit($openTask->title, 25),
                Str::limit($pendingTask->title, 25),
                Str::limit($closedTask->title, 25),
                Str::limit($completedTask->title, 25),
            ]);

        $this->actingAs($this->employee)->get(route('tasks.user', ['status' => 'pending']))
            ->assertOk()
            ->assertSeeText(Str::limit($pendingTask->title, 25))
            ->assertDontSeeText([
                $openTask,
                $closedTask,
                $completedTask
            ]);
    }

    public function test_an_admin_can_filter_all_tasks_based_on_the_status()
    {
        $openTask = Task::factory()->for($this->project)->create(['status' => 'open', 'user_id' => $this->employee->id]);
        $pendingTask = Task::factory()->for($this->project)->create(['status' => 'pending', 'user_id' => $this->employee->id]);
        $closedTask = Task::factory()->for($this->project)->create(['status' => 'closed', 'user_id' => $this->employee->id]);
        $completedTask = Task::factory()->for($this->project)->create(['status' => 'completed', 'user_id' => $this->employee->id]);

        $this->actingAs($this->admin)->get(route('tasks.index'))
            ->assertOk()
            ->assertSeeText([
                Str::limit($openTask->title, 25),
                Str::limit($pendingTask->title, 25),
                Str::limit($closedTask->title, 25),
                Str::limit($completedTask->title, 25),
            ]);

        $this->actingAs($this->admin)->get(route('tasks.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSeeText(Str::limit($pendingTask->title, 25))
            ->assertDontSeeText([
                $openTask,
                $closedTask,
                $completedTask
            ]);
    }

    public function test_an_admin_can_filter_all_tasks_on_their_title()
    {
        $project = Project::factory()->create();
        $firstTask = Task::factory()->for($this->project)->create(['title' => 'This is the first task']);
        $secondTask = Task::factory()->for($this->project)->create(['title' => 'This is the second task']);
        $thirdTask = Task::factory()->for($project)->create(['title' => 'This is task belongs to a different project']);

        $this->actingAs($this->admin)->get(route('tasks.index'))
            ->assertOk()
            ->assertSeeText([
                Str::limit($firstTask->title, 25),
                Str::limit($firstTask->project->title, 25),
                Str::limit($secondTask->title, 25),
                Str::limit($secondTask->project->title, 25),
                Str::limit($thirdTask->title, 25),
                Str::limit($thirdTask->project->title, 25),
            ]);


        $this->actingAs($this->admin)->get(route('tasks.index', ['search' => 'second']))
            ->assertOk()
            ->assertSeeText([
                Str::limit($secondTask->title, 25),
                Str::limit($secondTask->project->title, 25),
            ])
            ->assertDontSeeText([
                $firstTask->title,
                $thirdTask->title,
            ]);
    }

    public function test_a_user_can_filter_task_assigned_to_them_by_the_title_of_the_task()
    {
        $project = Project::factory()->create();
        $firstTask = Task::factory()->for($this->project)->create(['title' => 'This is the first task', 'user_id' => $this->employee->id]);
        $secondTask = Task::factory()->for($this->project)->create(['title' => 'This is the second task', 'user_id' => $this->employee->id]);
        $thirdTask = Task::factory()->for($project)->create(['title' => 'This is task belongs to a different project', 'user_id' => $this->employee->id]);

        $this->actingAs($this->employee)->get(route('tasks.user'))
            ->assertOk()
            ->assertSeeText([
                Str::limit($firstTask->title, 25),
                Str::limit($secondTask->title, 25),
                Str::limit($thirdTask->title, 25),
            ]);

        $this->actingAs($this->employee)->get(route('tasks.user', ['search' => 'first']))
            ->assertOk()
            ->assertSeeText([
                Str::limit($firstTask->title, 25),
                Str::limit($firstTask->project->title, 25),
            ])
            ->assertDontSeeText([
                $secondTask->title,
                $thirdTask->title,
            ]);
    }
}
