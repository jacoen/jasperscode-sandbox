<?php

namespace Tests\Feature\Controllers;

use App\Models\Project;
use App\Models\Task;
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

    protected $data;

    public function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create(['manager_id' => $this->manager->id]);

        $this->data = [
            'user_id' => $this->admin->id,
            'title' => 'A new task for testing',
            'description' => 'Here is a description for this new task',
        ];

        Storage::fake('media');

        Notification::fake();
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

    public function test_a_no_tasks_yet_message_will_be_displayed_if_the_current_user_without_an_admin_role_has_no_assigned_tasks_their_account()
    {
        $task = Task::factory()->for($this->project)->create(['user_id' => $this->manager->id]);

        $this->actingAs($this->employee)->get(route('tasks.index'))
            ->assertOk()
            ->assertSeeText('No tasks yet.');
    }


    public function test_it_truncates_the_task_and_project_titles_of_the_tasks_that_are_assigned_to_the_current_user()
    {
        $task = Task::factory()->for($this->project)->create(['user_id' => $this->employee->id]);

        $this->actingAs($this->employee)->get(route('tasks.index'))
            ->assertOk()
            ->assertSeeText([
                Str::limit($task->title, 25),
                Str::limit($this->project->title, 25),
                $task->status,
                $this->employee->name,
            ]);
    }

    public function test_a_guest_cannot_create_a_new_task()
    {
        $this->get(route('tasks.create', $this->project))->assertRedirect(route('login'));

        $this->post(route('tasks.store', $this->project), $this->data)
            ->assertRedirect(route('login'));

        $this->assertDatabaseMissing('tasks', $this->data);
    }

    public function test_a_user_without_the_create_task_permission_cannot_create_a_new_task()
    {
        $this->actingAs($this->user)->get(route('tasks.create', $this->project))
            ->assertForbidden();

        $this->actingAs($this->user)->post(route('tasks.store', $this->project), $this->data)
            ->assertForbidden();

        $this->assertDatabaseMissing('tasks', $this->data);
    }

    public function test_the_title_field_is_required_to_create_a_task()
    {
        $taskData = array_merge($this->data, ['title' => '']);

        $this->actingAs($this->employee)->post(route('tasks.store', $this->project), $taskData)
            ->assertSessionHasErrors([
                'title' => 'The title field is required.',
            ]);

        $this->assertDatabaseMissing('tasks', $taskData);
    }

    public function test_a_task_can_only_be_created_for_a_valid_project()
    {
        $this->actingAs($this->employee)->post(route('tasks.store', 99), $this->data)
            ->assertNotFound();

        $this->assertDatabaseMissing('tasks', [
            'title' => $this->data['title'],
            'project_id' => 99,
        ]);
    }

    public function test_a_user_cannot_upload_anything_other_than_an_image_when_creating_a_task()
    {
        $document = UploadedFile::fake()->create('test.txt', 15);
        $pdf = UploadedFile::fake()->create('test.pdf', 101);

        $this->actingAs($this->employee)->post(route('tasks.store', $this->project),
            array_merge($this->data, ['attachments' => [$document]])
        )->assertSessionHasErrors([
            'attachments.0' => 'The attachments may only contain images.',
        ]);

        $this->actingAs($this->employee)->post(route('tasks.store', $this->project),
            array_merge($this->data, ['attachments' => [$pdf]])
        )->assertSessionHasErrors([
            'attachments.0' => 'The attachments may only contain images.',
        ]);

        Storage::disk('media')->assertMissing($document);
        Storage::disk('media')->assertMissing($pdf);
    }

    public function test_a_user_can_upload_a_maximum_of_3_images_when_creating_a_task()
    {
        $image1 = UploadedFile::fake()->image('test1.jpg');
        $image2 = UploadedFile::fake()->image('test2.jpg');
        $image3 = UploadedFile::fake()->image('test3.jpg');
        $image4 = UploadedFile::fake()->image('test4.jpg');

        $taskData = array_merge($this->data,
            ['attachments' => [
                $image1,
                $image2,
                $image3,
                $image4,
            ],
        ]);

        $this->actingAs($this->employee)->post(route('tasks.store', $this->project), $taskData)
            ->assertSessionHasErrors([
                'attachments' => 'The attachments field must not have more than 3 items.',
            ]);

        $this->assertDatabaseMissing('tasks', ['title' => $taskData['title']]);

        $this->assertFileDoesNotExist($image1->getClientOriginalName());
        $this->assertFileDoesNotExist($image2->getClientOriginalName());
        $this->assertFileDoesNotExist($image3->getClientOriginalName());
        $this->assertFileDoesNotExist($image4->getClientOriginalName());
    }

    public function test_a_user_with_the_create_task_permission_can_create_a_new_task()
    {
        $this->actingAs($this->employee)->post(route('tasks.store', $this->project), $this->data)
            ->assertRedirect(route('projects.show', $this->project))
            ->assertSessionHas('success', 'A new task has been created.');

        $this->assertDatabaseHas('tasks', [
            'title' => $this->data['title'],
            'description' => $this->data['description'],
            'status' => 'open',
            'user_id' => $this->data['user_id'],
            'project_id' => $this->project->id,
        ]);
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

    public function test_a_user_with_the_read_task_permission_can_see_the_uploaded_image_of_a_task()
    {
        $task = Task::factory()->for($this->project)->create();

        $image = UploadedFile::fake()->image('attachment.jpg');

        $task->addMedia($image)->usingName($task->title)->toMediaCollection('attachments');

        $this->actingAs($this->employee)->get(route('tasks.show', $task))
            ->assertSee($task->getFirstMediaUrl('attachments'));
    }

    public function test_a_guest_cannot_visit_the_edit_task_route()
    {
        $task = Task::factory()->create();

        $this->get(route('tasks.edit', $task))->assertRedirect();
    }

    public function test_a_guest_cannot_update_a_task()
    {
        $task = Task::factory()->for($this->project)->create();

        $taskData = array_merge($this->data, ['status' => 'pending']);  

        $this->put(route('tasks.update', $task), $taskData)->assertRedirect('login');

        $this->assertDatabaseMissing('tasks', [
            'id' => $task->id,
            'title' => $taskData['title'],
            'description' => $taskData['description'],
            'status' => $taskData['status'],
        ]);
    }

    public function test_a_user_without_the_update_task_permission_cannot_visit_the_edit_task_route()
    {
        $task = Task::factory()->for($this->project)->create();

        $this->actingAs($this->user)->get(route('tasks.edit', $task))
            ->assertForbidden();
    }

    public function test_a_user_without_the_update_task_permission_cannot_update_a_task()
    {
        $task = Task::factory()->for($this->project)->create();

        $taskData = array_merge($this->data, ['status' => 'pending']);

        $this->actingAs($this->user)->put(route('tasks.update', $task), $taskData)
            ->assertForbidden();

        $this->assertDatabaseMissing('tasks', array_merge($taskData, ['id' => $task->id]));
    }

    public function test_the_title_and_status_fields_are_required_when_updating_a_task()
    {
        $task = Task::factory()->for($this->project)->create();

        $taskData = array_merge($this->data, [
            'title' => '',
            'status' => '',
        ]);

        $this->actingAs($this->employee)->put(route('tasks.update', $task), $taskData)
            ->assertSessionHasErrors([
                'title' => 'The title field is required.',
                'status' => 'The status field is required.',
            ]);

        $this->assertNotEquals($task->fresh()->title, $taskData['title']);
        $this->assertNotEquals($task->fresh()->status, $taskData['status']);

        $this->assertDatabaseMissing('tasks', array_merge($taskData, ['id' => $task->id]));
    }

    public function test_a_user_with_the_update_task_permission_can_visit_the_edit_task_route()
    {
        $task = Task::factory()->for($this->project)->create();
        
        $this->actingAs($this->employee)->get(route('tasks.edit', $task));
    }

    public function test_a_user_with_update_task_permission_can_update_an_existing_task()
    {
        $task = Task::factory()->for($this->project)->create();

        $taskData = array_merge($this->data, ['status' => 'pending']);

        $this->actingAs($this->employee)->put(route('tasks.update', $task), $taskData)
            ->assertRedirect(route('tasks.show', $task))
            ->assertSessionHas('success', 'The task '.$task->fresh()->title.' has been updated.');

        $this->assertDatabaseHas('tasks', array_merge($taskData, ['id' => $task->id]));
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
            ->assertSessionHas('success', 'The task has been deleted.');

        $this->assertSoftDeleted($task);
    }

    public function test_a_guest_cannot_visit_the_trashed_task_overview_page()
    {
        $this->get(route('tasks.trashed'))->assertRedirect('login');
    }

    public function test_a_user_without_the_restore_task_permission_cannot_visit_the_trashed_task_overview_page()
    {
        $this->actingAs($this->employee)->get(route('tasks.trashed'))
            ->assertForbidden();
    }

    public function test_a_user_with_the_restore_task_permission_can_visit_the_trashed_task_overview_page()
    {
        $this->actingAs($this->manager)->get(route('tasks.trashed'))
            ->assertOk();
    }

    public function test_a_no_tasks_yet_message_will_be_displayed_on_the_trashed_tasks_overview_when_there_are_no_trashed_tasks_yet()
    {
        $this->actingAs($this->manager)->get(route('tasks.trashed'))
            ->assertOk()
            ->assertSeeText('No tasks yet.');   
    }

    public function test_the_project_and_task_titles_are_truncated_on_the_trashed_task_overview()
    {
        $task = Task::factory()->for($this->project)->trashed()->create();

        $this->actingAs($this->manager)->get(route('tasks.trashed'))
            ->assertOk()
            ->assertSeeText([
                Str::limit($task->title, 30),
                Str::limit($this->project->title, 30)
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

    public function test_a_user_with_the_restore_task_permission_can_restore_the_task_of_an_active_project()
    {
        $task = Task::factory()->for($this->project)->trashed()->create();

        $this->actingAs($this->manager)->patch(route('tasks.restore', $task))
            ->assertRedirect(route('projects.show', $this->project))
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

    public function test_an_admin_can_reach_the_admin_task_overview_page()
    {
        $this->actingAs($this->admin)->get(route('admin.tasks'))
            ->assertOk();
    }

    public function test_it_truncates_the_task_and_project_titles_when_an_admin_visit_the_admin_task_overview_page()
    {
        $task = Task::factory()->for($this->project)->create(['author_id' => $this->manager->id]);
        $this->actingAs($this->admin)->get(route('admin.tasks'))
            ->assertSeeText([
                Str::limit($task->title, 25),
                Str::limit($this->project->title, 25),
                $task->status,
            ]);
    }

    public function teardown():void
    {
        Storage::disk('media')->deleteDirectory('');

        parent::tearDown();
    }
}
