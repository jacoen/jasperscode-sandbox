<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class TaskControllerTest extends TestCase
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

    public function test_a_user_with_the_read_task_permission_that_does_not_have_the_admin_role_can_only_see_tasks_that_are_assigned_to_them()
    {
        $secondUser = User::factory()->create()->assignRole('Employee');

        $this->actingAs($this->employee)->get(route('tasks.index'))
            ->assertOk()
            ->assertSeeText('No tasks yet.');

        $task = Task::factory()->for($this->project)->create(['user_id' => $this->employee->id]);
        $secondTask = Task::factory()->for($this->project)->create(['user_id' => $secondUser->id]);

        $this->actingAs($this->employee)->get(route('tasks.index'))
            ->assertOk()
            ->assertSeeText([
                Str::limit($task->title, 25),
                Str::limit($this->project->title, 25),
                $task->status,
                $this->employee->name
            ])
            ->assertDontSeeText([$secondTask->title]);
    }

    public function test_an_employee_can_filter_their_own_task_on_the_status_of_the_task ()
    {
        $openTask = Task::factory()->for($this->project)->create(['status' => 'open', 'user_id' => $this->employee->id]);
        $pendingTask = Task::factory()->for($this->project)->create(['status' => 'pending', 'user_id' => $this->employee->id]);
        $closedTask = Task::factory()->for($this->project)->create(['status' => 'closed', 'user_id' => $this->employee->id]);
        $completedTask = Task::factory()->for($this->project)->create(['status' => 'completed', 'user_id' => $this->employee->id]);

        $this->actingAs($this->employee)->get(route('tasks.index'))
            ->assertOk()
            ->assertSeeText([
                Str::limit($openTask->title, 25),
                Str::limit($pendingTask->title, 25),
                Str::limit($closedTask->title, 25),
                Str::limit($completedTask->title, 25),
            ]);

        $this->actingAs($this->employee)->get(route('tasks.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSeeText(Str::limit($pendingTask->title, 25))
            ->assertDontSeeText([
                Str::limit($openTask->title, 25),
                Str::limit($closedTask->title, 25),
                Str::limit($completedTask->title, 25),
            ]);
    }

    public function test_an_employee_can_filter_their_assigned_tasks_on_the_title_of_the_task()
    {
        $firstTask = Task::factory()->for($this->project)->create(['user_id' => $this->employee->id, 'title' => 'Sample for a new task']);
        $secondTask = Task::factory()->for($this->project)->create(['user_id' => $this->employee->id, 'title' => 'A brand new task for this test']);

        $this->actingAs($this->employee)->get(route('tasks.index'))
            ->assertOk()
            ->assertSeeText([
                Str::limit($firstTask->title, 25),
                Str::limit($secondTask->title, 25),
            ]);

        $this->actingAs($this->employee)->get(route('tasks.index', ['search' => 'brand']))
            ->assertOk()
            ->assertSeeText(Str::limit($secondTask->title, 25))
            ->assertDontSeeText(Str::limit($firstTask->title, 25));
    }

    public function test_a_guest_cannot_create_a_new_task()
    {
        $task = [
            'title' => 'some simple task',
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
        ];

        $this->get(route('tasks.create', $this->project))->assertRedirect(route('login'));

        $this->post(route('tasks.store', $this->project), $task)
            ->assertRedirect(route('login'));

        $this->assertDatabaseMissing('tasks', $task);
    }

    public function test_a_user_without_the_create_task_permission_cannot_create_a_new_task()
    {
        $task = [
            'title' => 'some simple task',
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
        ];

        $this->actingAs($this->user)->get(route('tasks.create', $this->project))
            ->assertForbidden();

        $this->actingAs($this->user)->post(route('tasks.store', $this->project), $task)
            ->assertForbidden();

        $this->assertDatabaseMissing('tasks', $task);
    }

    public function test_the_title_field_is_required_to_create_a_task()
    {
        $task = [
            'title' => '',
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
        ];

        $this->actingAs($this->employee)->post(route('tasks.store', $this->project))
            ->assertSessionHasErrors([
                'title' => 'The title field is required.',
            ]);

        $this->assertDatabaseMissing('tasks', $task);
    }

    public function test_a_task_cannot_be_created_for_a_trashed_project()
    {
        $trashedProject = Project::factory()->trashed()->create();

        $data = [
            'title' => 'a title for a task of the trashed project',
            'description' => 'A sample description',
            'user_id' => $this->employee->id,
        ];

        $this->actingAs($this->employee)->post(route('tasks.store', $this->project), 
            array_merge($data, ['project_id' => $trashedProject->id])
        );

        $this->assertDatabaseMissing('tasks', [
            'title' => $data['title'],
            'project_id' => $trashedProject->id,
        ]);
    }

    public function test_a_task_cannot_be_created_for_a_closed_project()
    {
        $closedProject = Project::factory()->create(['status' => 'closed']);

        $data = [
            'title' => 'a title for a task of the trashed project',
            'description' => 'A sample description',
            'user_id' => $this->employee->id,
        ];

        $this->actingAs($this->employee)->post(route('tasks.store', $this->project), 
            array_merge($data, ['project_id' => $closedProject->id])
        );

        $this->assertDatabaseMissing('tasks', [
            'title' => $data['title'],
            'project_id' => $closedProject->id,
        ]);
    }

    public function test_a_task_cannot_be_created_for_a_completed_project()
    {
        $completedProject = Project::factory()->create(['status' => 'completed']);

        $data = [
            'title' => 'a title for a task of the trashed project',
            'description' => 'A sample description',
            'user_id' => $this->employee->id,
        ];

        $this->actingAs($this->employee)->post(route('tasks.store', $this->project), 
            array_merge($data, ['project_id' => $completedProject->id])
        );

        $this->assertDatabaseMissing('tasks', [
            'title' => $data['title'],
            'project_id' => $completedProject->id,
        ]);
    }

    public function test_a_task_cannot_be_created_for_a_restored_project()
    {
        $restoredProject = Project::factory()->create(['status' => 'restored']);

        $data = [
            'title' => 'a title for a task of the trashed project',
            'description' => 'A sample description',
            'user_id' => $this->employee->id,
        ];

        $this->actingAs($this->employee)->post(route('tasks.store', $this->project), 
            array_merge($data, ['project_id' => $restoredProject->id])
        );
        
        $this->assertDatabaseMissing('tasks', [
            'title' => $data['title'],
            'project_id' => $restoredProject->id,
        ]);
    }

    public function test_a_task_can_only_be_created_for_a_valid_project()
    {
        $data = [
            'title' => 'a title for a task of the trashed project',
            'description' => 'A sample description',
            'user_id' => $this->employee->id,
        ];

        $this->actingAs($this->employee)->post(route('tasks.store', $this->project), 
            array_merge($data, ['project_id' => 99])
        );

        $this->assertDatabaseMissing('tasks', [
            'title' => $data['title'], 
            'project_id' => 99,    
        ]);
    }

    public function test_a_user_cannot_upload_anything_other_than_an_image_when_creating_or_updating_a_task()
    {
        Storage::fake('media');

        $document = UploadedFile::fake()->create('test.txt', 15);
        $pdf = UploadedFile::fake()->create('test.pdf', 101);

        $data =  [
            'title' => 'A sample with media as attachment',
            'description' => 'These samples should fail the upload',
            'user_id' => $this->employee->id,
        ];

        $this->actingAs($this->employee)->post(route('tasks.store', $this->project),
            array_merge($data, ['attachments' => [$document]])
        )->assertSessionHasErrors([
            'attachments.0' => 'The attachments may only contain images.'
        ]);

        $this->actingAs($this->employee)->post(route('tasks.store', $this->project),
            array_merge($data, ['attachments' => [$pdf]])
        )->assertSessionHasErrors([
            'attachments.0' => 'The attachments may only contain images.'
        ]);

        Storage::disk('media')->assertMissing($document);
        Storage::disk('media')->assertMissing($pdf);
    }

    public function test_a_user_can_upload_a_maximum_of_3_images_when_creating_or_updating_a_task()
    {
        Storage::fake('media');

        $image1 = UploadedFile::fake()->image('test1.jpg');
        $image2 = UploadedFile::fake()->image('test2.jpg');
        $image3 = UploadedFile::fake()->image('test3.jpg');
        $image4 = UploadedFile::fake()->image('test4.jpg');

        $data = [
            'title' => 'over the image limit',
            'user_id' => $this->employee->id,
            'attachments' => [
                $image1, $image2, $image3, $image4,
            ],
        ];

        $this->actingAs($this->employee)->post(route('tasks.store', $this->project), $data)
            ->assertSessionHasErrors([
                'attachments' => 'The attachments field must not have more than 3 items.'
            ]);

        $this->assertDatabaseMissing('tasks', ['title' => $data['title']]);

        $this->assertFileDoesNotExist($image1->getClientOriginalName());
        $this->assertFileDoesNotExist($image2->getClientOriginalName());
        $this->assertFileDoesNotExist($image3->getClientOriginalName());
        $this->assertFileDoesNotExist($image4->getClientOriginalName());
    }

    public function test_a_user_with_the_create_task_permission_can_create_a_new_task()
    {
        $data = [
            'title' => 'A simple task for this test',
            'description' => 'A sample description',
            'user_id' => $this->employee->id,
        ];

        $this->actingAs($this->employee)->post(route('tasks.store', $this->project), $data)
            ->assertRedirect(route('projects.show', $this->project))
            ->assertSessionHas('success', 'A new task has been created.');

        $this->actingAs($this->employee)->get(route('projects.show', $this->project))
            ->assertOk()
            ->assertSeeText([
                $data['title'],
                $this->employee->name,
                'Open',
            ]);

        $this->assertDatabaseHas('tasks', [
            'title' => $data['title'],
            'description' => $data['description'],
            'status' => 'open',
            'user_id' => $data['user_id'],
            'project_id' => $this->project->id,
        ]);
    }

    public function test_a_user_can_upload_an_image_as_attachment_when_creating_or_updating_a_task()
    {
        Storage::fake('media');

        $file = UploadedFile::fake()->image('test.jpg');

        $data = [
            'title' => 'Sample task with image',
            'user_id' => $this->employee->id,
            'attachments' => [$file],
        ];

        $this->actingAs($this->employee)->post(route('tasks.store', $this->project), $data)
            ->assertRedirect(route('projects.show', $this->project))
            ->assertSessionHas('success', 'A new task has been created.');

        $task = Task::first();

        $this->assertEquals($task->getFirstMedia('attachments')->file_name, $file->getClientOriginalName());
        $this->assertFileExists($task->getFirstMedia('attachments')->getPath());

        Storage::disk('media')->assertExists('/'.$task->id.'/'.$file->getClientOriginalName());
    }

    public function test_when_a_tasks_get_assigned_to_a_user_this_user_receives_a_notification()
    {
        Notification::fake();

        $data = [
            'title' => 'simple test',
            'description' => 'sample description',
            'user_id' => $this->employee->id,
        ];

        $this->actingAs($this->employee)->post(route('tasks.store', $this->project), $data)
            ->assertRedirect(route('projects.show', $this->project))
            ->assertSessionHas('success');

        Notification::assertSentTo($this->employee, TaskAssignedNotification::class);
    }

    public function test_a_guest_cannot_visit_the_show_task_page()
    {
        $task = Task::factory()->create();
        $this->get(route('tasks.show', $task))->assertRedirect(route('login'));
    }

    public function test_a_user_without_the_read_task_permission_cannot_visit_the_show_task()
    {
        $task = Task::factory()->create();

        $this->actingAs($this->user)->get(route('tasks.show', $task))
            ->assertForbidden();
    }

    public function test_a_user_with_the_read_task_permission_can_visit_the_show_task_page()
    {
        $task = Task::factory()->for($this->project)->create();

        $this->actingAs($this->employee)->get(route('tasks.show', $task))
            ->assertOk()
            ->assertSeeText([
                $task->title,
                $task->description,
                $task->user->name,
                $task->project->title,
            ]);
    }

    public function test_a_guest_cannot_edit_a_task()
    {
        $task = Task::factory()->for($this->project)->create();

        $data = [
            'title' => 'A brand new title for this task',
            'description' => $task->description,
            'status' => $task->status,
        ];

        $this->get(route('tasks.edit', $task))->assertRedirect('login');

        $this->put(route('tasks.update', $task), $data)->assertRedirect('login');

        $this->assertDatabaseMissing('tasks', [
            'id' => $task->id,
            'title' => $data['title'],
            'description' => $data['description'],
            'status' => $data['status']
        ]);
    }

    public function test_a_user_without_the_update_task_permission_cannot_edit_a_task()
    {
        $task = Task::factory()->for($this->project)->create();

        $data = [
            'title' => 'A brand new title for this task',
            'description' => $task->description,
            'status' => $task->status,
        ];

        $this->actingAs($this->user)->get(route('tasks.edit', $task))
            ->assertForbidden();

        $this->actingAs($this->user)->put(route('tasks.update', $task), $data)
            ->assertForbidden();

        $this->assertDatabaseMissing('tasks', array_merge($data, ['id' => $task->id]));
    }

    public function test_a_user_cannot_update_a_task_of_a_closed_project()
    {
        $closedProject = Project::factory()->create(['status' => 'closed']);
        $task = Task::factory()->for($closedProject)->create();

        $data = [
            'title' => 'A brand new title for this task',
            'description' => $task->description,
            'status' => $task->status,
        ];

        $this->actingAs($this->employee)->put(route('tasks.update', $task), $data)
            ->assertSessionHasErrors(['error' => 'Could not update this task because the project is inactive.']);

        $this->assertDatabaseMissing('tasks', array_merge($data, ['id' => $task->id]));
    }

    public function test_a_user_cannot_update_a_task_of_a_completed_project()
    {
        $completedProject = Project::factory()->create(['status' => 'completed']);
        $task = Task::factory()->for($completedProject)->create(['user_id' => $this->employee->id]);

        $data = [
            'title' => 'A brand new title for this task',
            'description' => $task->description,
            'status' => $task->status,
        ];

        $this->actingAs($this->employee)->put(route('tasks.update', $task), $data)
            ->assertSessionHasErrors(['error' => 'Could not update this task because the project is inactive.']);

        $this->assertDatabaseMissing('tasks', array_merge($data, ['id' => $task->id]));
    }

    public function test_a_user_cannot_update_a_task_of_a_restored_project()
    {
        $restoredProject = Project::factory()->create(['status' => 'restored']);
        $task = Task::factory()->for($restoredProject)->create();

        $data = [
            'title' => 'A brand new title for this task',
            'description' => $task->description,
            'status' => $task->status,
        ];

        $this->actingAs($this->employee)->put(route('tasks.update', $task), $data)
            ->assertSessionHasErrors(['error' => 'Could not update this task because the project is inactive.']);

        $this->assertDatabaseMissing('tasks', array_merge($data, ['id' => $task->id]));
    }

    public function test_the_title_and_status_fields_are_required_when_updating_a_task()
    {
        $task = Task::factory()->for($this->project)->create();

        $data = [
            'title' => '',
            'description' => $task->description,
            'status' => '',
        ];


        $this->actingAs($this->employee)->put(route('tasks.update', $task), $data)
            ->assertSessionHasErrors([
                'title' => 'The title field is required.',
                'status' => 'The status field is required.',
            ]);

        $this->assertNotEquals($task->fresh()->title, $data['title']);
        $this->assertNotEquals($task->fresh()->status, $data['status']);

        $this->assertDatabaseMissing('tasks', array_merge($data, ['id' => $task->id]));
    }

    public function test_a_user_with_update_task_permission_can_update_an_existing_task()
    {
        $task = Task::factory()->for($this->project)->create();

        $data = [
            'title' => 'A brand new title for this task',
            'description' => $task->description,
            'status' => $task->status,
        ];

        $this->actingAs($this->employee)->put(route('tasks.update', $task), $data)
            ->assertRedirect(route('tasks.show', $task))
            ->assertSessionHas('success', 'The task '.$task->fresh()->title.' has been updated.');

        $this->assertDatabaseHas('tasks', array_merge($data, ['id' => $task->id]));
    }

    public function test_when_an_existing_task_gets_assigned_to_another_user_this_user_receives_a_notification()
    {
        $user = User::factory()->create()->assignRole('Employee');
        $employee = User::factory()->create()->assignRole('Admin');
        $task = Task::factory()->create(['user_id' => $user->id]);

        Notification::fake();

        $data = [
            'title' => 'This is a simple test',
            'status' => 'pending',
            'user_id' => $employee->id,
        ];

        $this->actingAs($user)->put(route('tasks.update', $task), $data)
            ->assertRedirect(route('tasks.show', $task));

        $this->assertDatabaseHas('tasks', array_merge($data, ['id' => $task->id]));

        Notification::assertSentTo($employee,TaskAssignedNotification::class);
    }

    public function test_guests_cannot_delete_a_task()
    {
        $task = Task::factory()->for($this->project)->create(['user_id' => $this->employee->id]);

        $this->delete(route('tasks.destroy', $task))
            ->assertRedirect(route('login'));

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
            'user_id' => $this->employee->id,
        ]);

        $this->actingAs($this->employee)->delete(route('tasks.destroy', $task))
            ->assertRedirect(route('projects.show', $task->project))
            ->assertSessionHas('success', 'The task '.$task->title.' has been deleted.');

        $this->assertSoftDeleted($task);
    }

    public function test_a_guest_cannot_visit_the_trashed_task_overview_page()
    {
        $task1 = Task::factory()->trashed()->for($this->project)->create();
        $task2 = Task::factory()->trashed()->for($this->project)->create();
        $task3 = Task::factory()->trashed()->for($this->project)->create();

        $this->get(route('tasks.trashed'))->assertRedirect('login');
    }

    public function test_a_user_without_the_restore_task_permission_cannot_visit_the_trashed_task_overview_page()
    {
        $task1 = Task::factory()->trashed()->for($this->project)->create();
        $task2 = Task::factory()->trashed()->for($this->project)->create();
        $task3 = Task::factory()->trashed()->for($this->project)->create();

        $this->actingAs($this->employee)->get(route('tasks.trashed'))
            ->assertForbidden();
    }

    public function test_a_user_with_the_restore_task_permission_can_visit_the_trashed_task_overview_page()
    {
        $task1 = Task::factory()->for($this->project)->trashed()->create(['user_id' => null]);
        $task2 = Task::factory()->for($this->project)->trashed()->create(['user_id' => null]);
        $task3 = Task::factory()->for($this->project)->trashed()->create(['user_id' => null]);

        $this->actingAs($this->manager)->get(route('tasks.trashed'))
            ->assertOk()
            ->assertSeeText([
                Str::limit($task1->title, 30), Str::limit($task1->project->title, 30),
                Str::limit($task2->title, 30), Str::limit($task2->project->title, 30),
                Str::limit($task3->title, 30), Str::limit($task3->project->title, 30),
            ]);
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

    public function test_a_user_with_the_restore_task_permission_cannot_restore_a_task_when_the_related_project_has_been_trashed()
    {
        $project = Project::factory()->trashed()->create();
        $task = Task::factory()->for($project)->trashed()->create();
        
        $this->assertTrue($project->trashed());
        $this->assertTrue($task->trashed());

        $this->actingAs($this->manager)->patch(route('tasks.restore', $task))
            ->assertSessionHasErrors('error', 'Could not restore task because the project has been deleted.');

        $this->assertSoftDeleted($task);
    }

    public function test_a_user_with_the_restore_task_permission_cannot_restore_a_task_when_the_related_project_has_been_closed()
    {
        $closedProject = Project::factory()->create(['status' => 'closed']);
        $firstTask = Task::factory()->for($closedProject)->trashed()->create();

        $completedProject = Project::factory()->create(['status' => 'completed']);
        $secondTask = Task::factory()->for($completedProject)->trashed()->create();

        $this->actingAs($this->manager)->patch(route('tasks.restore', $firstTask))
            ->assertRedirect(route('tasks.trashed'))
            ->assertSessionHasErrors('error', 'Could not restore task becaues the project is either closed or completed.');

        $this->actingAs($this->manager)->patch(route('tasks.restore', $secondTask))
            ->assertSessionHasErrors('error', 'Could not restore task becaues the project is either closed or completed.');
    }

    public function test_a_user_with_the_restore_task_permission_can_restore_the_task_of_an_active_project()
    {
        $task = Task::factory()->for($this->project)->trashed()->create();

        $this->actingAs($this->manager)->patch(route('tasks.restore', $task))
            ->assertRedirect(route('tasks.trashed'))
            ->assertSessionHas('success', 'The task '.$task->title.'has been restored.');

        $this->assertNotSoftDeleted($task);
    }

    public function test_a_guest_cannot_permanently_delete_a_task()
    {
        $task = Task::factory()->for($this->project)->trashed()->create();

        $this->patch(route('tasks.force-delete', $task))
            ->assertRedirect(route('login'));

        $this->assertSoftDeleted($task);
    }

    public function test_a_user_without_the_force_delete_authorization_cannot_permanently_delete_a_task()
    {
        $task = Task::factory()->for($this->project)->trashed()->create();

        $this->actingAs($this->manager)->patch(route('tasks.force-delete', $task))
            ->assertForbidden();

        $this->assertSoftDeleted($task);
    }

    public function test_a_user_with_the_force_delete_authorization_can_permanently_delete_a_task()
    {
        $task = Task::factory()->for($this->project)->trashed()->create();

        $this->actingAs($this->admin)->patch(route('tasks.force-delete', $task))
            ->assertRedirect(route('tasks.trashed'))
            ->assertSessionHas('success', 'The task has been permanently deleted.');

        $this->assertDatabaseMissing('tasks', [
            'id' => $task->id,
            'title' => $task->title,
        ]);
    }

    public function test_guests_cannot_visit_the_admin_task_overview_page()
    {
        $this->get(route('admin.tasks'))->assertRedirect(route('login'));
    }

    public function test_a_user_without_the_admin_role_cannot_visit_the_admin_task_overview_page()
    {
        $this->actingAs($this->employee)->get(route('admin.tasks'))
            ->assertForbidden();

        $this->actingAs($this->manager)->get(route('admin.tasks'))
            ->assertForbidden();
    }

    public function test_an_admin_can_visit_the_admin_task_overview_page()
    {
        $tasks = Task::factory(2)->create(['user_id' => $this->employee->id]);
        $trashedTask = Task::factory()->trashed()->create();

        $firstTask = Task::first();
        $secondTask = Task::latest()->first();

        $this->actingAs($this->admin)->get(route('admin.tasks'))
            ->assertOk()
            ->assertSeeText([
                Str::limit($firstTask->title, 25),
                Str::limit($firstTask->project->title, 25),
                $firstTask->status,
                $this->employee->name,
                Str::limit($secondTask->title, 25),
                Str::limit($secondTask->title, 25),
                $secondTask->status,
            ])
            ->assertDontSeeText($trashedTask->title);
    }

    protected function teardown(): void
    {
        Storage::disk('media')->deleteDirectory('');

        parent::tearDown();
    }
}
