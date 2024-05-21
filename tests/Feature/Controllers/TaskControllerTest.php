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

    public function test_a_user_with_the_read_task_permission_without_assigned_tasks_will_see_the_no_assigned_tasks_message_when_visiting_the_task_index_page()
    {
        $task = Task::factory()->for($this->project)->create(['user_id' => $this->manager->id]);

        $this->actingAs($this->employee)->get(route('tasks.index'))
            ->assertOk()
            ->assertSeeText('No tasks yet.');
    }

    public function test_a_user_with_the_read_task_permission_can_see_all_their_asssigned_tasks_when_visiting_the_task_index_page()
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

    public function test_a_guest_cannot_visit_the_create_task_page()
    {
        $this->get(route('tasks.create', $this->project))->assertRedirect(route('login'));
    }

    public function test_a_user_without_the_create_task_permission_cannot_visit_the_create_task_page()
    {
        $this->actingAs($this->user)->get(route('tasks.create', $this->project))
            ->assertForbidden();
    }

    public function test_a_user_with_the_create_task_permission_can_visit_the_create_task_page()
    {
        $this->actingAs($this->employee)->get(route('tasks.create', $this->project))
            ->assertOk();
    }

    public function test_the_user_will_see_an_error_message_when_they_try_to_create_a_task_whilst_the_related_project_is_inactive()
    {
        $project = Project::factory()->create(['status' => 'completed']);

        $this->actingAs($this->employee)->get(route('tasks.create', $project))
            ->assertRedirect(route('projects.show', $project))
            ->assertSessionHasErrors([
                'error' => 'Cannot create a task when the project is inactive.',
            ]);
    }

    public function test_a_valid_user_must_be_provided_when_creating_a_task()
    {
        $data = [
            'title' => 'Some title',
            'user_id' => 99,
        ];

        $this->actingAs($this->employee)->post(route('tasks.store', $this->project), $data)
            ->assertSessionHasErrors([
                'user_id' => 'The selected user is invalid.',
            ]);
    }

    public function test_the_title_field_is_required_to_create_a_task()
    {
        $taskData = array_merge($this->data, ['title' => '']);

        $this->actingAs($this->employee)->post(route('tasks.store', $this->project), $taskData)
            ->assertSessionHasErrors([
                'title' => 'The title field is required.',
            ]);

        $this->assertEquals(0, Task::count());
    }

    public function test_the_title_must_be_at_least_3_characters_long_when_creating_a_task()
    {
        $this->actingAs($this->employee)->post(route('tasks.store', $this->project), [
            'title' => 'aa',
        ])->assertSessionHasErrors([
            'title' => 'The title field must be at least 3 characters.',
        ]);

        $this->assertEquals(0, Task::count());
    }

    public function test_the_title_cannot_be_longer_than_255_characters_when_creating_a_task()
    {
        $this->actingAs($this->employee)->post(route('tasks.store', $this->project), [
            'title' => Str::repeat('abc', 120),
        ])->assertSessionHasErrors([
            'title' => 'The title field must not be greater than 255 characters.',
        ]);

        $this->assertEquals(0, Task::count());
    }

    public function test_the_description_must_be_at_least_3_characters_long_when_creating_a_task()
    {
        $this->actingAs($this->employee)->post(route('tasks.store', $this->project), array_merge($this->data, [
            'description' => 'aa',
        ]))->assertSessionHasErrors([
            'description' => 'The description field must be at least 3 characters.',
        ]);

        $this->assertEquals(0, Task::count());
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

        $this->assertEquals(0, Task::count());
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

        $this->assertEquals(0, Task::count());
    }

    public function test_a_guest_cannot_create_a_new_task()
    {
        $this->post(route('tasks.store', $this->project), $this->data)
            ->assertRedirect(route('login'));

        $this->assertEquals(0, Task::count());
    }

    public function test_a_user_without_the_create_task_permission_cannot_create_a_new_task()
    {
        $this->actingAs($this->user)->post(route('tasks.store', $this->project), $this->data)
            ->assertForbidden();

        $this->assertEquals(0, Task::count());
    }

    public function test_a_user_with_the_create_task_permission_can_create_a_new_task()
    {
        $this->actingAs($this->employee)->post(route('tasks.store', $this->project), $this->data)
            ->assertRedirect(route('projects.show', $this->project))
            ->assertSessionHas('success', 'A new task has been created.');

        $this->assertEquals(1, Task::count());

        $task = Task::first();
        $this->assertEquals($task->title, $this->data['title']);
        $this->assertEquals($task->description, $this->data['description']);
        $this->assertEquals($task->user_id, $this->data['user_id']);
        $this->assertEquals($task->author_id, $this->employee->id);
    }

    public function test_a_guest_cannot_visit_the_task_detail_page()
    {
        $task = Task::factory()->create();

        $this->get(route('tasks.show', $task))
            ->assertRedirect(route('login'));
    }

    public function test_a_user_without_the_read_task_permission_cannot_visit_the_project_detail_page()
    {
        $task = Task::factory()->create();

        $this->actingAs($this->user)->get(route('tasks.show', $task))
            ->assertForbidden();
    }

    public function test_a_user_with_the_read_task_permission_can_visit_the_show_task_page()
    {
        $task = Task::factory()->for($this->project)->create();

        $image = UploadedFile::fake()->image('attachment.jpg');
        $task->addMedia($image)->usingName($task->title)->toMediaCollection('attachments');

        $this->actingAs($this->employee)->get(route('tasks.show', $task))
            ->assertOk()
            ->assertSeeText([
                $task->title,
                $task->description,
                $task->user->name,
                $task->project->title,
            ])->assertSee($task->getFirstMediaUrl('attachments'));
    }

    public function test_a_guest_cannot_visit_the_edit_task_page()
    {
        $task = Task::factory()->create();

        $this->get(route('tasks.edit', $task))->assertRedirect(route('login'));
    }

    public function test_a_user_without_the_update_task_permission_cannot_visit_the_edit_task_page()
    {
        $task = Task::factory()->create();

        $this->actingAs($this->user)->get(route('tasks.edit', $task))
            ->assertForbidden();
    }

    public function test_a_user_with_the_update_task_permission_can_visit_the_edit_task_page()
    {
        $task = Task::factory()->create();

        $this->actingAs($this->employee)->get(route('tasks.edit', $task))
            ->assertOk()
            ->assertSeeText($task->title);
    }

    public function test_the_user_will_see_an_error_message_when_they_want_to_edit_a_task_whilst_the_related_project_is_inactive()
    {
        $project = Project::factory()->create(['status' => 'completed']);
        $task = Task::factory()->for($project)->create();

        $this->actingAs($this->employee)->get(route('tasks.edit', $task))
            ->assertRedirect(route('projects.show', $project))
            ->assertSessionHasErrors([
                'error' => 'Cannot edit the task because the related project is inactive.',
            ]);
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
    }

    public function test_a_valid_user_must_be_provided_when_updating_a_task()
    {
        $task = Task::factory()->create();

        $this->actingAs($this->employee)->put(route('tasks.update', $task), array_merge($this->data, [
            'user_id' => 9999,
        ]))->assertSessionHasErrors([
            'user_id' => 'The selected user is invalid.',
        ]);

        $this->assertNotEquals($task->fresh()->user_id, 9999);
    }

    public function test_the_title_must_be_at_least_3_characters_long_when_updating_a_task()
    {
        $task = Task::factory()->create();

        $this->actingAs($this->employee)->put(route('tasks.update', $task), array_merge($this->data, [
            'title' => 'aa',
        ]))->assertSessionHasErrors([
            'title' => 'The title field must be at least 3 characters.',
        ]);

        $this->assertNotEquals($task->fresh()->title, 'aa');
    }

    public function test_the_title_cannot_be_longer_than_255_characters_when_updating_a_task()
    {
        $task = Task::factory()->create();

        $this->actingAs($this->employee)->put(route('tasks.update', $task), array_merge($this->data, [
            'title' => Str::repeat('abc', 120),
        ]))->assertSessionHasErrors([
            'title' => 'The title field must not be greater than 255 characters.',
        ]);

        $this->assertNotEquals($task->fresh()->title, Str::repeat('abc', 120));
    }

    public function test_description_must_be_at_least_3_characters_long_when_updating_a_task()
    {
        $task = Task::factory()->create();

        $this->actingAs($this->employee)->put(route('tasks.update', $task), array_merge($this->data, [
            'description' => 'aa',
        ]))->assertSessionHasErrors([
            'description' => 'The description field must be at least 3 characters.',
        ]);

        $this->assertNotEquals($task->fresh()->description, 'aa');
    }

    public function test_the_status_must_be_valid_when_updating_a_task()
    {
        $task = Task::factory()->create();

        $this->actingAs($this->employee)->put(route('tasks.update', $task), array_merge($this->data, [
            'status' => 'restored',
        ]))->assertSessionHasErrors([
            'status' => 'The selected status is invalid.',
        ]);
    }

    public function test_the_attachment_can_only_be_an_image_when_updating_a_task()
    {
        $task = Task::factory()->create();

        $document = UploadedFile::fake()->create('test.txt', 15, 'txt');

        $this->actingAs($this->employee)->put(route('tasks.update', $task), array_merge($this->data, [
            'attachments' => [$document],
        ]))->assertSessionHasErrors([
            'attachments.0' => 'The attachments may only contain images.',
        ]);

        $this->assertNull($task->getFirstMedia('media'));
        Storage::disk('media')->assertMissing($document);
    }

    public function test_a_maximum_of_3_attachments_can_be_uploaded_when_updating_a_task()
    {
        $task = Task::factory()->create();

        $image1 = UploadedFile::fake()->image('test1.jpg');
        $image2 = UploadedFile::fake()->image('test2.jpg');
        $image3 = UploadedFile::fake()->image('test3.jpg');
        $image4 = UploadedFile::fake()->image('test4.jpg');

        $taskData = array_merge($this->data,
            [
                'status' => 'pending',
                'attachments' => [
                    $image1,
                    $image2,
                    $image3,
                    $image4,
                ],
            ]);

        $this->actingAs($this->employee)->put(route('tasks.update', $task), $taskData)
            ->assertSessionHasErrors([
                'attachments' => 'The attachments field must not have more than 3 items.',
            ]);

        $this->assertEmpty($task->fresh()->getMedia('media'));
        Storage::disk('media')->assertMissing($image1);
        Storage::disk('media')->assertMissing($image2);
        Storage::disk('media')->assertMissing($image3);
        Storage::disk('media')->assertMissing($image4);
    }

    public function test_a_guest_cannot_update_a_task()
    {
        $task = Task::factory()->create();

        $this->put(route('tasks.update', $task), $this->data)
            ->assertRedirect(route('login'));
    }

    public function test_a_user_without_the_update_task_permission_cannot_update_a_task()
    {
        $task = Task::factory()->create();

        $this->actingAs($this->user)->put(route('tasks.update', $task), array_merge($this->data, [
            'status' => 'pending',
            'user_id' => $this->employee->id,
        ]))->assertForbidden();

        $task->refresh();
        $this->assertNotEquals($task->title, $this->data['title']);
        $this->assertNotEquals($task->description, $this->data['description']);
        $this->assertNotEquals($task->user_id, $this->employee->id);
        $this->assertNotEquals($task->status, 'pending');
    }

    public function test_a_user_with_the_update_task_permission_can_update_a_task()
    {
        $task = Task::factory()->create();

        $this->actingAs($this->employee)->put(route('tasks.update', $task), array_merge($this->data, [
            'status' => 'pending',
            'user_id' => $this->employee->id,
        ]))->assertRedirect(route('tasks.show', $task))
            ->assertSessionHas('success', 'The task '.$task->fresh()->title.' has been updated.');

        $task->refresh();
        $this->assertEquals($task->title, $this->data['title']);
        $this->assertEquals($task->description, $this->data['description']);
        $this->assertEquals($task->user_id, $this->employee->id);
        $this->assertEquals($task->status, 'pending');
    }

    public function test_the_user_gets_redirected_when_an_update_task_exception_occurs()
    {
        $project = Project::factory()->create(['status' => 'closed']);
        $task = Task::factory()->for($project)->create();

        $this->actingAs($this->employee)->put(route('tasks.update', $task), array_merge($this->data, [
            'status' => 'pending',
            'user_id' => $this->employee->id,
        ]))->assertRedirect(route('tasks.show', $task))
            ->assertSessionHasErrors([
                'error' => 'Cannot update the task because the project is inactive.',
            ]);
    }

    public function test_a_guest_cannot_delete_a_task()
    {
        $task = Task::factory()->create();

        $this->delete(route('tasks.destroy', $task))
            ->assertRedirect(route('login'));

        $this->assertNotSoftDeleted($task);
        $this->assertEquals(1, Task::count());
    }

    public function test_a_user_without_the_delete_task_permission_cannot_delete_a_task()
    {
        $task = Task::factory()->create();

        $this->actingAs($this->user)->delete(route('tasks.destroy', $task))
            ->assertForbidden();

        $this->assertNotSoftDeleted($task);
        $this->assertEquals(1, Task::count());
    }

    public function test_a_user_with_the_delete_task_permission_can_delete_a_task()
    {
        $task = Task::factory()->for($this->project)->create();

        $this->actingAs($this->employee)->delete(route('tasks.destroy', $task))
            ->assertRedirect(route('projects.show', $this->project))
            ->assertSessionHas('success', 'The task has been deleted.');

        $this->assertSoftDeleted($task);
        $this->assertEquals(1, Task::onlyTrashed()->count());
    }

    public function test_a_guest_cannot_visit_the_trashed_task_page()
    {
        $this->get(route('tasks.trashed'))
            ->assertRedirect();
    }

    public function test_a_user_without_the_restore_task_permission_cannot_visit_the_trashed_task_page()
    {
        $this->actingAs($this->user)->get(route('tasks.trashed'))
            ->assertForbidden();
    }

    public function test_a_user_with_the_restore_task_permission_will_see_a_no_tasks_message_when_there_are_no_trashed_tasks()
    {
        $this->actingAs($this->employee)->get(route('tasks.trashed'))
            ->assertOk()
            ->assertSeeText('No tasks yet');
    }

    public function test_a_user_with_the_restore_task_permission_can_see_all_the_trashed_tasks_when_visiting_the_trashed_tasks_page()
    {
        $task = Task::factory()->trashed()->create();
        $activeTask = Task::factory()->create();

        $this->actingAs($this->employee)->get(route('tasks.trashed'))
            ->assertOk()
            ->assertSeeText([
                Str::limit($task->title, 30),
                Str::limit($task->project->title, 30),
                lastUpdated($task->deleted_at),
            ])
            ->assertDontSeeText([
                Str::limit($activeTask->title),
            ]);
    }

    public function test_guest_cannot_restore_a_task()
    {
        $task = Task::factory()->trashed()->create();

        $this->patch(route('tasks.restore', $task))
            ->assertRedirect(route('login'));

        $this->assertEquals(1, Task::onlyTrashed()->count());
    }

    public function test_a_user_without_the_restore_task_permission_cannot_restore_a_task()
    {
        $task = Task::factory()->trashed()->create();

        $this->actingAs($this->user)->patch(route('tasks.restore', $task))
            ->assertForbidden();

        $this->assertEquals(1, Task::onlyTrashed()->count());
    }

    public function test_a_user_with_the_restore_task_permission_can_restore_a_task()
    {
        $task = Task::factory()->trashed()->create();

        $this->actingAs($this->employee)->patch(route('tasks.restore', $task))
            ->assertRedirect(route('tasks.trashed'))
            ->assertSessionHas('success', 'The task has been restored.');

        $this->assertEquals(0, Task::onlyTrashed()->count());
    }

    public function test_a_guest_cannot_delete_a_task_pemanently()
    {
        $task = Task::factory()->trashed()->create();

        $this->delete(route('tasks.force-delete', $task))
            ->assertRedirect(route('login'));

        $this->assertEquals(1, Task::withTrashed()->count());
    }

    public function test_a_user_without_correct_permissions_cannot_delete_a_task_permanently()
    {
        $task = Task::factory()->trashed()->create();

        $this->actingAs($this->employee)->delete(route('tasks.force-delete', $task))
            ->assertForbidden();

        $this->assertEquals(1, Task::withTrashed()->count());
    }

    public function test_a_user_with_the_correct_permissions_can_delete_a_task_permanently()
    {
        $task = Task::factory()->trashed()->create();

        $this->actingAs($this->admin)->delete(route('tasks.force-delete', $task))
            ->assertRedirect(route('tasks.trashed'))
            ->assertSessionHas('success', 'The task has been permanently deleted.');
    }

    public function test_a_guest_cannot_visit_the_admin_task_page()
    {
        $this->get(route('admin.tasks'))->assertRedirect(route('login'));
    }

    public function test_a_user_without_the_admin_role_cannot_visit_the_admin_task_page()
    {
        $this->actingAs($this->employee)->get(route('admin.tasks'))
            ->assertForbidden();
    }

    public function test_a_user_with_the_admin_role_will_see_the_no_tasks_yet_message_when_they_visit_the_admin_task_page_when_there_are_no_tasks()
    {
        $this->actingAs($this->admin)->get(route('admin.tasks'))
            ->assertOk()
            ->assertSeeText('No tasks yet.');
    }

    public function test_a_user_with_the_admin_role_can_see_all_active_tasks_on_the_admin_task_page()
    {
        $employeeTask = Task::factory()->create(['user_id' => $this->employee->id]);
        $managerTask = Task::factory()->create(['user_id' => $this->manager->id]);
        $adminTask = Task::factory()->create(['user_id' => $this->admin->id]);

        $this->actingAs($this->admin)->get(route('admin.tasks'))
            ->assertOk()
            ->assertSeeText([
                Str::limit($employeeTask->title, 25),
                Str::limit($employeeTask->project->title, 25),
                $employeeTask->status,
                $this->employee->name,
            ])->assertSeeText([
                Str::limit($managerTask->title, 25),
                Str::limit($managerTask->project->title, 25),
                $managerTask->status,
                $this->manager->name,
            ])->assertSeeText([
                Str::limit($adminTask->title, 25),
                Str::limit($adminTask->project->title, 25),
                $adminTask->status,
                $this->admin->name,
            ]);
    }

    public function teardown(): void
    {
        Storage::disk('media')->deleteDirectory('');

        parent::tearDown();
    }
}
