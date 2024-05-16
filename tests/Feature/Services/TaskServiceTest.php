<?php

namespace Tests\Feature\Services;

use App\Exceptions\CreateTaskException;
use App\Exceptions\InvalidProjectStatusException;
use App\Exceptions\ProjectDeletedException;
use App\Exceptions\UpdateTaskException;
use App\Models\Project;
use App\Models\Task;
use App\Notifications\TaskAssignedNotification;
use App\Services\TaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TaskServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $taskService;

    public function setUp():void
    {
        parent::setUp();

        $this->taskService = app(TaskService::class);

        Storage::fake('media');

        Notification::fake();
    }

    public function test_it_can_list_all_tasks()
    {
        Task::factory(10)->create();
        $task = Task::factory()->create(['title' => 'Some title']);

        $tasks = $this->taskService->listTasks();

        $this->assertInstanceOf(LengthAwarePaginator::class, $tasks);

        $this->assertEquals(11, $tasks->count());

        $this->assertTrue($tasks->contains($task));
    }

    public function test_it_can_search_tasks_by_title()
    {
        Task::factory(10)->create();
        $task = Task::factory()->create(['title' => 'A smaller title is sometimes handy']);
        $secondTask = Task::factory()->create(['title' => 'A title for a bigger task']);

        $tasks = $this->taskService->listTasks('smaller');

        $this->assertInstanceOf(LengthAwarePaginator::class, $tasks);

        $this->assertEquals(1, $tasks->count());

        $this->assertTrue($tasks->contains($task));
        $this->assertFalse($tasks->contains($secondTask));
    }

    public function test_it_can_filter_tasks_by_status()
    {
        $openTask = Task::factory()->create(['status' => 'open']);
        $pendingTask = Task::factory()->create(['status' => 'pending']);
        $closedTask = Task::factory()->create(['status' => 'closed']);
        $completedTask = Task::factory()->create(['status' => 'completed']);
        $restoredTask = Task::factory()->create([
            'created_at' => now()->subMinutes(3),
            'updated_at' => now()->subMinute(),
            'status' => 'restored',
        ]);

        $tasks = $this->taskService->listTasks('', 'pending');

        $this->assertInstanceOf(LengthAwarePaginator::class, $tasks);

        $this->assertEquals(1, $tasks->count());

        $this->assertTrue($tasks->contains($pendingTask));
        $this->assertFalse($tasks->contains($openTask));
        $this->assertFalse($tasks->contains($closedTask));
        $this->assertFalse($tasks->contains($completedTask));
        $this->assertFalse($tasks->contains($restoredTask));
    }

    public function test_it_can_filter_tasks_by_the_user()
    {
        $managerTask = Task::factory()->create(['author_id' => $this->employee->id, 'user_id' => $this->manager->id]);
        $employeeTask = Task::factory()->create(['author_id' => $this->manager->id, 'user_id' => $this->employee->id]);

        $tasks = $this->taskService->listTasks('', '', $this->employee->id);

        $this->assertInstanceOf(LengthAwarePaginator::class, $tasks);

        $this->assertEquals(1, $tasks->count());

        $this->assertTrue($tasks->contains($employeeTask));
        $this->assertFalse($tasks->contains($managerTask));
    }

    public function test_it_throws_an_exception_when_creating_a_task_for_a_closed_or_completed_project()
    {
        $project = Project::factory()->create(['status' => 'closed']);

        $validData = [
            'title' => 'Hier is een task',
            'description' => 'Hier is een omschrijving',
            'user_id' => $this->employee->id,
        ];

        $this->expectException(CreateTaskException::class);
        $this->taskService->storeTask($project, $validData);

        $this->assertDatabaseMissing('tasks', $validData);
    }

    public function test_it_throws_an_exception_when_creating_a_task_for_a_restored_project()
    {
        $project = Project::factory()->create(['status' => 'restored']);

        $validData = [
            'title' => 'Hier is een task',
            'description' => 'Hier is een omschrijving',
            'user_id' => $this->employee->id,
        ];

        $this->expectException(CreateTaskException::class);
        $this->taskService->storeTask($project, $validData);

        $this->assertDatabaseMissing('tasks', $validData);
    }

    public function test_it_throws_an_exception_when_creating_a_task_for_an_expired_project()
    {
        $project = Project::factory()->create(['status' => 'expired']);

        $validData = [
            'title' => 'Hier is een task',
            'description' => 'Hier is een omschrijving',
            'user_id' => $this->employee->id,
        ];

        $this->expectException(CreateTaskException::class);
        $this->taskService->storeTask($project, $validData);

        $this->assertDatabaseMissing('tasks', $validData);
    }

    public function test_it_can_create_a_task_when_the_project_status_is_open_or_pending()
    {
        $project = Project::factory()->create(['status' => 'open']);

        $validData = [
            'title' => 'Hier is een task',
            'description' => 'Hier is een omschrijving',
            'author_id' => $this->employee->id,
        ];

        $task = $this->taskService->storeTask($project, $validData);

        $this->assertInstanceOf(Task::class, $task);
        $this->assertEquals($task->project_id, $project->id);
        $this->assertEquals($task->author_id, $this->employee->id);
        $this->assertTrue($task->status == 'open');

        $this->assertDatabaseHas('tasks', $validData);
    }

    public function test_it_can_upload_attachments_when_creating_a_task()
    {
        $project = Project::factory()->create(['status' => 'open']);
        $file = UploadedFile::fake()->image('test.jpg');

        $validData = [
            'title' => 'Hier is een task',
            'description' => 'Hier is een omschrijving',
            'author_id' => $this->employee->id,
        ];

        $task = $this->taskService->storeTask($project, $validData, [$file]);

        $this->assertFileExists($task->getFirstMedia('attachments')->getPath());
        $this->assertEquals($task->getFirstMedia('attachments')->file_name, $file->getClientOriginalName());

        Storage::disk('media')->assertExists('/'.$task->getFirstMedia('attachments')->id.'/'.$file->getClientOriginalName());
    }

    public function test_it_sends_a_notification_when_a_created_task_has_been_assigned_to_a_user()
    {
        $project = Project::factory()->create(['status' => 'open']);

        $validData = [
            'title' => 'Hier is een task',
            'description' => 'Hier is een omschrijving',
            'author_id' => $this->employee->id,
            'user_id' => $this->manager->id,
        ];

        $this->taskService->storeTask($project, $validData);

        Notification::assertSentTo($this->manager, TaskAssignedNotification::class);
    }
    
    public function test_it_throws_an_exception_when_updating_a_task_for_a_closed_or_completed_project()
    {
        $project = Project::factory()->create(['status' => 'closed']);
        $task = Task::factory()->for($project)->create(['status' => 'open', 'author_id' => $this->employee->id]);

        $validData = [
            'title' => 'Een aangepaste titel',
            'description' => 'Een aangepaste omschrijving',
            'author_id' => $task->author_id,
        ];

        $this->expectException(UpdateTaskException::class);
        $this->taskService->updateTask($task, $validData);

        $this->assertNotEquals($task->fresh()->title, $validData['title']);
        $this->assertNotEquals($task->fresh()->description, $validData['description']);
  
        $this->assertDatabaseMissing('tasks', array_merge($validData, ['id' => $task->id]));
    }

    public function test_it_throws_an_exception_when_updating_a_task_for_a_restored_project()
    {
        $project = Project::factory()->create(['status' => 'restored']);
        $task = Task::factory()->for($project)->create(['status' => 'open', 'author_id' => $this->employee->id]);

        $validData = [
            'title' => 'Een aangepaste titel',
            'description' => 'Een aangepaste omschrijving',
            'author_id' => $task->author_id,
        ];

        $this->expectException(UpdateTaskException::class);
        $this->taskService->updateTask($task, $validData);

        $this->assertNotEquals($task->fresh()->title, $validData['title']);
        $this->assertNotEquals($task->fresh()->description, $validData['description']);

        $this->assertDatabaseMissing('tasks', array_merge($validData, ['id' => $task->id]));
    }

    public function test_it_throws_an_exception_when_updating_a_task_for_an_expired_project()
    {
        $project = Project::factory()->create(['status' => 'expired']);
        $task = Task::factory()->for($project)->create(['status' => 'open', 'author_id' => $this->employee->id]);

        $validData = [
            'title' => 'Een aangepaste titel',
            'description' => 'Een aangepaste omschrijving',
            'author_id' => $task->author_id,
        ];

        $this->expectException(UpdateTaskException::class);
        $this->taskService->updateTask($task, $validData);

        $this->assertNotEquals($task->fresh()->title, $validData['title']);
        $this->assertNotEquals($task->fresh()->description, $validData['description']);

        $this->assertDatabaseMissing('tasks', array_merge($validData, ['id' => $task->id]));
    }

    public function test_it_can_update_a_task_when_the_project_status_is_open_or_pending()
    {
        $project = Project::factory()->create(['status' => 'pending']);
        $task = Task::factory()->for($project)->create(['author_id' => $this->employee->id]);

        $validData = [
            'title' => 'Een aangepaste titel',
            'description' => 'Een aangepaste omschrijving',
            'author_id' => $task->author_id,
        ];

        $this->taskService->updateTask($task, $validData);

        $task = $task->fresh();

        $this->assertEquals($task->title, $validData['title']);
        $this->assertEquals($task->description, $validData['description']);

        $this->assertDatabaseHas('tasks', array_merge($validData, ['id' => $task->id]));
    }

    public function test_it_can_upload_attachments_to_an_existing_task_without_media_when_the_task_has_been_updated()
    {
        $project = Project::factory()->create(['status' => 'pending']);
        $task = Task::factory()->for($project)->create(['author_id' => $this->employee->id]);

        $file = UploadedFile::fake()->image('test.jpg');

        $validData = [
            'title' => 'Een aangepaste titel',
            'description' => 'Een aangepaste omschrijving',
            'author_id' => $task->author_id,
        ];

        $task = $this->taskService->updateTask($task, $validData, [$file]);

        $this->assertFileExists($task->getFirstMedia('attachments')->getPath());
        $this->assertEquals($task->getFirstMedia('attachments')->file_name, $file->getClientOriginalName());

        Storage::disk('media')->assertExists('/'.$task->getFirstMedia('attachments')->id.'/'.$file->getClientOriginalName());
    }

    public function test_the_current_media_of_an_existing_task_will_remain_when_the_task_gets_updated_without_new_media()
    {
        $project = Project::factory()->create(['status' => 'pending']);
        $task = Task::factory()->for($project)->create(['author_id' => $this->employee->id]);

        $file = UploadedFile::fake()->image('test.jpg');
        $task->addMedia($file)->toMediaCollection('attachments');

        $validData = [
            'title' => 'Een aangepaste titel',
            'description' => 'Een aangepaste omschrijving',
            'author_id' => $task->author_id,
        ];

        $task = $this->taskService->updateTask($task, $validData);

        $this->assertFileExists($task->fresh()->getFirstMedia('attachments')->getPath());
        $this->assertEquals($task->fresh()->getFirstMedia('attachments')->file_name, $file->getClientOriginalName());

        Storage::disk('media')->assertExists('/'.$task->fresh()->getFirstMedia('attachments')->id.'/'.$file->getClientOriginalName());
    }

    public function test_the_existing_attachment_get_replaced_by_a_new_attachment_when_the_task_gets_updated_with_a_new_attachment()
    {
        $project = Project::factory()->create(['status' => 'pending']);
        $task = Task::factory()->for($project)->create(['author_id' => $this->employee->id]);

        $oldFile = UploadedFile::fake()->image('old_test.jpg');
        $task->addMedia($oldFile)->toMediaCollection('attachments');

        $file = UploadedFile::fake()->image('new_test.jpg');

        $validData = [
            'title' => 'Een aangepaste titel',
            'description' => 'Een aangepaste omschrijving',
            'author_id' => $task->author_id,
        ];

        $task = $this->taskService->updateTask($task, $validData, [$file]);

        $this->assertFileDoesNotExist($oldFile->getPathname());
        $this->assertEmpty($task->getMedia('attachments')->where('file_name', 'old_test.jpg'));
        $this->assertNotEquals($task->fresh()->getFirstMedia('attachments')->file_name, $oldFile->getClientOriginalName());

        $this->assertFileExists($task->fresh()->getFirstMedia('attachments')->getPath());
        $this->assertEquals($task->fresh()->getFirstMedia('attachments')->file_name, $file->getClientOriginalName());

        Storage::disk('media')->assertExists('/'.$task->fresh()->getFirstMedia('attachments')->id.'/'.$file->getClientOriginalName());
    }

    public function test_it_sends_a_notification_when_an_updated_task_has_been_assigned_to_a_different_user()
    {
        $project = Project::factory()->create(['status' => 'pending']);
        $task = Task::factory()->for($project)->create([
            'author_id' => $this->employee->id, 
            'user_id' => $this->manager->id
        ]);

        $validData = [
            'title' => 'Een aangepaste titel',
            'description' => 'Een aangepaste omschrijving',
            'user_id' => $this->employee->id,
        ];

        $task = $this->taskService->updateTask($task, $validData);

        Notification::assertSentTo($this->employee, TaskAssignedNotification::class);
    }

    public function test_it_can_only_list_all_soft_deleted_tasks()
    {
        $activeTask = Task::factory()->create();
        Task::factory(3)->trashed()->create();

        $tasks = $this->taskService->trashedTasks();

        $this->assertInstanceOf(LengthAwarePaginator::class, $tasks);

        $this->assertCount(3, $tasks);

        $this->assertFalse($tasks->contains($activeTask));
    }

    public function test_it_throws_an_exception_when_restoring_a_task_for_a_closed_or_completed_project()
    {
        $project = Project::factory()->create(['status' => 'closed']);
        $task = Task::factory()->for($project)->trashed()->create();

        $this->expectException(InvalidProjectStatusException::class);
        $this->taskService->restoreTask($task);

        $this->assertSoftDeleted($task);
    }

    public function test_it_throws_an_exception_when_restoring_a_task_for_a_trashed_project()
    {
        $project = Project::factory()->trashed()->create();
        $task = Task::factory()->for($project)->trashed()->create();

        $this->expectException(ProjectDeletedException::class);
        $this->taskService->restoreTask($task);

        $this->assertSoftDeleted($task);
    }

    public function test_it_throws_an_exception_when_restoring_a_task_for_an_expired_project()
    {
        $project = Project::factory()->create(['status' => 'expired']);
        $task = Task::factory()->for($project)->trashed()->create();

        $this->expectException(InvalidProjectStatusException::class);
        $this->taskService->restoreTask($task);

        $this->assertSoftDeleted($task);
    }

    public function test_it_can_restore_a_soft_deleted_task_of_a_project_with_the_open_or_pending_status()
    {
        $project = Project::factory()->create(['status' => 'pending']);
        $task = Task::factory()->for($project)->trashed()->create();

        $this->taskService->restoreTask($task);

        $this->assertNull($task->deleted_at);
        $this->assertNotSoftDeleted($task);
    }

    public function test_it_can_list_all_active_tasks_of_a_project()
    {
        $project = Project::factory()->create();
        Task::factory(5)->for($project)->create();
        $task = Task::factory()->for($project)->create();

        $tasks = $this->taskService->findTasksByProject($project);

        $this->assertInstanceOf(LengthAwarePaginator::class, $tasks);

        $this->assertCount(6, $tasks);

        $this->assertTrue($tasks->contains($task));
    }

    public function test_it_can_search_all_active_tasks_of_a_project_by_title()
    {
        $project = Project::factory()->create();
        Task::factory(5)->for($project)->create();
        $task = Task::factory()->for($project)->create(['title' => 'some new task']);

        $tasks = $this->taskService->findTasksByProject($project, 'some');

        $this->assertInstanceOf(LengthAwarePaginator::class, $tasks);

        $this->assertCount(1, $tasks);

        $otherTask = Task::first();
        $this->assertNotEquals($task->title, $otherTask->title);

        $this->assertFalse($tasks->contains($otherTask));
        $this->assertTrue($tasks->contains($task));
    }

    public function test_it_can_filter_all_active_tasks_of_a_project_by_status()
    {
        $project = Project::factory()->create();
        $openTask = Task::factory()->for($project)->create(['status' => 'open']);
        $pendingTask = Task::factory()->for($project)->create(['status' => 'pending']);
        $closedTask = Task::factory()->for($project)->create(['status' => 'closed']);
        $completedTask = Task::factory()->for($project)->create(['status' => 'completed']);
        $restoredTask = Task::factory()->for($project)->create([
            'created_at' => now()->subMinutes(3),
            'updated_at' => now()->subMinute(),
            'status' => 'restored',
        ]);

        $tasks = $this->taskService->findTasksByProject($project, '', 'completed');

        $this->assertCount(1, $tasks);

        $this->assertTrue($tasks->contains($completedTask));
        $this->assertFalse($tasks->contains($pendingTask));
        $this->assertFalse($tasks->contains($openTask));
        $this->assertFalse($tasks->contains($closedTask));
        $this->assertFalse($tasks->contains($restoredTask));
    }
}
