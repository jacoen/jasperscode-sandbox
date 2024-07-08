<?php

namespace Tests\Feature\Controllers\Api;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TaskControllerTest extends TestCase
{
    use RefreshDatabase;

    protected array $data;
    protected Project $project;
    protected string $storeTaskUrl;

    public function setUp(): void
    {
        parent::setUp();

        $this->data = [
            'title' => 'This is a new task',
            'description' => 'Here we describe what we want to happen in the task.',
        ];

        Storage::fake('media');

        $this->project = Project::factory()->create(['manager_id' => $this->manager->id]);
        $this->storeTaskUrl = '/api/v1/projects/'.$this->project->id.'/tasks';
    }

    public function test_a_guest_is_not_authorized_to_access_the_tasks_overview_endpoint()
    {
        $this->getJson('/api/v1/tasks')->assertUnauthorized();
    }

    public function test_a_user_without_the_read_tasks_permission_is_not_authorized_to_access_the_tasks_overview_endpoint()
    {
        $this->actingAs($this->user)->getJson('/api/v1/tasks')
            ->assertForbidden();
    }

    public function test_an_empty_array_is_returned_when_the_user_with_the_read_tasks_permission_has_no_tasks_assigned_to_them_whilst_accessing_the_tasks_overview_endpoint()
    {
        $user = User::factory()->create()->assignRole('Employee');
        $task = Task::factory()->create(['user_id' => $this->employee->id]);

        $this->actingAs($user)->getJson('/api/v1/tasks')
            ->assertOk()
            ->assertJsonFragment([
                'data' => [],
            ]);
    }

    public function test_authored_tasks_and_the_assigned_tasks_are_returned_when_the_user_with_the_read_tasks_permission_accesses_the_tasks_overview_endpoint()
    {
        $authoredTask = Task::factory()->for($this->project)->for($this->employee, 'author')->create(['user_id' => null]);
        $assignedTask = Task::factory()->for($this->project)->create([
            'user_id' => $this->employee->id,
        ]);
        
        Task::factory()->create();

        $this->actingAs($this->employee)->getJson('/api/v1/tasks')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'id' => $authoredTask->id,
                'title' => $authoredTask->title,
                'status' => $authoredTask->status,
                'author' => $authoredTask->author->name,
                'user' => 'Not assigned'
            ])->assertJsonFragment([
                'id' => $assignedTask->id,
                'title' => $assignedTask->title,
                'status' => $assignedTask->status,
                'author' => $assignedTask->author->name,
                'user' => $assignedTask->user->name
            ]);
    }

    public function test_title_field_is_required_when_creating_a_task()
    {
        $this->actingAs($this->employee)->postJson($this->storeTaskUrl, [
            'title' => ''
            ])->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title' => 'The title field is required.',
            ]);

        $this->assertEquals(0, Task::count());
    }

    public function test_provided_title_and_description_must_be_at_least_3_characters_long_when_creating_a_task()
    {
        $this->actingAs($this->employee)->postJson($this->storeTaskUrl, [
            'title' => 'ab',
            'description' => 'ab',
            ])->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title' => 'The title field must be at least 3 characters.',
                'description' => 'The description field must be at least 3 characters.',
            ]);

        $this->assertEquals(0, Task::count());
    }

    public function test_the_provided_title_cannot_be_longer_than_255_characters_when_creating_a_task()
    {
        $this->actingAs($this->employee)->postJson($this->storeTaskUrl, [
            'title' => str_repeat('abc', 90),
            ])->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title' => 'The title field must not be greater than 255 characters.',
            ]);

        $this->assertEquals(0, Task::count());
    }

    public function test_a_valid_user_must_be_provided_when_creating_a_task()
    {
        $taskData = array_merge($this->data, ['user_id' => 999]);

        $this->actingAs($this->employee)->postJson($this->storeTaskUrl, $taskData)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'user_id' => 'The selected user is invalid.'
            ]);

        $this->assertEquals(0, Task::count());
    }

    public function test_the_provided_attachments_cannot_be_anything_but_images_when_creating_a_project()
    {
        $document = UploadedFile::fake()->create('test.txt', 15);
        $pdf = UploadedFile::fake()->create('test.pdf', 101);

        $this->actingAs($this->employee)->postJson($this->storeTaskUrl, array_merge(
            $this->data,
            ['attachments' => [$document]])
        )->assertUnprocessable()
            ->assertJsonValidationErrors([
                'attachments.0' => 'The attachments may only contain images.'
            ]);

        $this->actingAs($this->employee)->postJson($this->storeTaskUrl, array_merge(
            $this->data,
            ['attachments' => [$pdf]])
        )->assertUnprocessable()
            ->assertJsonValidationErrors([
                'attachments.0' => 'The attachments may only contain images.'
            ]);

        Storage::disk('media')->assertMissing($document);
        Storage::disk('media')->assertMissing($pdf);

        $this->assertEquals(0, Task::count());
    }

    public function test_a_maximum_of_three_images_can_be_upload_whilst_creating_a_task()
    {
        $image1 = UploadedFile::fake()->image('test1.jpg');
        $image2 = UploadedFile::fake()->image('test2.jpg');
        $image3 = UploadedFile::fake()->image('test3.jpg');
        $image4 = UploadedFile::fake()->image('test4.jpg');

        $taskData = array_merge($this->data,
            [
                'attachments' => [
                    $image1,
                    $image2,
                    $image3,
                    $image4,
                ],
            ]);

        $this->actingAs($this->employee)->postJson($this->storeTaskUrl, $taskData)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'attachments' => 'The attachments field must not have more than 3 items.',
            ]);

        $this->assertEquals(0, Task::count());

        $this->assertFileDoesNotExist($image1->getClientOriginalName());
        $this->assertFileDoesNotExist($image2->getClientOriginalName());
        $this->assertFileDoesNotExist($image3->getClientOriginalName());
        $this->assertFileDoesNotExist($image4->getClientOriginalName());
    }

    public function test_a_guest_cannot_access_the_create_task_endpoint()
    {
        $this->postJson($this->storeTaskUrl, $this->data)->assertUnauthorized();

        $this->assertEquals(0, Task::count());
    }

    public function test_a_user_without_the_create_task_permission_cannot_access_the_create_task_endpoint()
    {
        $this->actingAs($this->user)->postJson($this->storeTaskUrl, $this->data)
            ->assertForbidden();

        $this->assertEquals(0, Task::count());
    }

    public function test_a_user_with_the_create_task_permission_can_access_the_create_task_endpoint()
    {
        $taskData = array_merge($this->data, [
            'user_id' => $this->manager->id,
        ]);

        $this->actingAs($this->employee)->postJson($this->storeTaskUrl, $taskData)
            ->assertCreated()
            ->assertJsonFragment([
                'title' => $this->data['title'],
                'description' => $this->data['description'],
                'status' => 'open',
                'user' => $this->manager->name,
                'author' => $this->employee->name,
            ]);

        $this->assertEquals(1, Task::count());
    }

    public function test_a_guest_cannot_access_the_the_task_detail_endpoint()
    {
        $task = Task::factory()->create();

        $this->getJson('/api/v1/tasks/'.$task->id)
            ->assertUnauthorized();
    }

    public function test_a_user_without_the_read_task_permission_cannot_access_the_task_detail_endpoint()
    {
        $task = Task::factory()->create();

        $this->actingAs($this->user)->getJson('/api/v1/tasks/'. $task->id)
            ->assertForbidden();

    }

    public function test_a_user_with_the_read_task_permission_can_access_the_task_detail_endpoint()
    {
        $task = Task::factory()->create();

        $this->actingAs($this->employee)->getJson('/api/v1/tasks/'.$task->id)
            ->assertOk()
            ->assertJsonFragment([
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'status' => $task->status,
                'author' => $task->author->name,
                'user' => $task->user->name,
                'updated_at' => [
                    'human' => $task->updated_at->diffForHumans(),
                    'date_time' => $task->updated_at->toDateTimeString(),
                ],
            ])->assertJsonFragment([
                'id' => $task->project->id,
                'title' => $task->project->title,
                'status' => $task->project->status,
                'due_date' => $task->project->due_date->format('d M Y'), 
            ]);
    }

    public function test_the_title_and_status_fields_are_required_when_updating_a_task()
    {
        $data = [
            'title' => '',
            'status' => '',
        ];

        $task = Task::factory()->create();

        $this->actingAs($this->employee)->putJson('/api/v1/tasks/'.$task->id, $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title' => 'The title field is required.',
                'status' => 'The status field is required.',
            ]);

        $task->refresh();
        $this->assertNotNull($task->title);
        $this->assertNotNull($task->status);
    }

    public function test_the_provided_title_and_description_must_have_a_minimum_length_of_3_characters_when_updating_a_task()
    {
        $data = [ 
            'title' => 'ab',
            'description' => 'ab',
            'status' => 'pending',
        ];

        $task = Task::factory()->create();

        $this->actingAs($this->employee)->putJson('/api/v1/tasks/'.$task->id, $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title' => 'The title field must be at least 3 characters.',
                'description' => 'The description field must be at least 3 characters.',
            ]);

        $task->refresh();
        $this->assertNotEquals($task->title, $data['title']);
        $this->assertNotEquals($task->description, $data['description']);
        $this->assertNotEquals($task->status, $data['status']);
    }

    public function test_the_provided_title_cannot_be_longer_than_255_characters_when_updating_a_task()
    {
        $data = [
            'title' => str_repeat('abc', 90),
            'status' => 'pending',
        ];

        $task = Task::factory()->create();

        $this->actingAs($this->employee)->putJson('/api/v1/tasks/'.$task->id, $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title' => 'The title field must not be greater than 255 characters.',
            ]);

        $this->assertNotEquals($task->fresh()->title, $data['title']);
    }

    public function test_the_provided_status_must_be_valid_when_updating_a_task()
    {
        $task = Task::factory()->create();

        $data = [
            'title' => $task->title,
            'status' => 'restored',
        ];

        $this->actingAs($this->employee)->putJson('api/v1/tasks/'.$task->id, $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'status' => 'The selected status is invalid.',
            ]);

        $this->assertNotEquals($task->status, $data['status']);
    }

    public function test_only_images_can_be_provided_as_attachments_when_updating_a_task()
    {
        $task = Task::factory()->create();

        $document = UploadedFile::fake()->create('test.txt', 15);
        $pdf = UploadedFile::fake()->create('test.pdf', 101);

        $taskData = array_merge($this->data, [
            'status' => 'pending',
            'user_id' => $this->manager->id,
        ]);

        $this->actingAs($this->employee)->putJson('/api/v1/tasks/'.$task->id, array_merge($taskData, [
                'attachments' => [$document]
            ]))->assertUnprocessable()
            ->assertJsonValidationErrors([
                'attachments.0' => 'The attachments may only contain images.'
            ]);

        $this->actingAs($this->employee)->putJson('/api/v1/tasks/'.$task->id, array_merge($taskData, [
                'attachments' => [$pdf]
            ]))->assertUnprocessable()
            ->assertJsonValidationErrors([
                'attachments.0' => 'The attachments may only contain images.'
            ]);

        Storage::disk('media')->assertMissing($document);
        Storage::disk('media')->assertMissing($pdf);
    }

    public function test_a_maximum_of_3_images_can_be_provided_as_attachments_when_updating_a_task()
    {
        $image1 = UploadedFile::fake()->image('test1.jpg');
        $image2 = UploadedFile::fake()->image('test2.jpg');
        $image3 = UploadedFile::fake()->image('test3.jpg');
        $image4 = UploadedFile::fake()->image('test4.jpg');

        $taskData = array_merge($this->data, [
            'status' => 'pending',
            'user_id' => $this->manager->id,
            'attachments' => [
                $image1,
                $image2,
                $image3,
                $image4
            ],
        ]);

        $task = Task::factory()->create();

        $this->actingAs($this->employee)->putJson('/api/v1/tasks/'.$task->id, $taskData)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'attachments' => 'The attachments field must not have more than 3 items.',
            ]);

        $this->assertFileDoesNotExist($image1->getClientOriginalName());
        $this->assertFileDoesNotExist($image2->getClientOriginalName());
        $this->assertFileDoesNotExist($image3->getClientOriginalName());
        $this->assertFileDoesNotExist($image4->getClientOriginalName());
    }

    public function test_a_guest_cannot_access_the_update_task_endpoint()
    {
        $taskData = array_merge($this->data, [
            'status' => 'pending',
        ]);

        $task = Task::factory()->create();

        $this->putJson('/api/v1/tasks/'.$task->id, $taskData)
            ->assertUnauthorized();

        $this->assertNotEquals($task->fresh()->title, $taskData['title']);

    }

    public function test_a_user_without_the_update_task_permission_cannot_access_the_update_task_endpoint()
    {
        $taskData = array_merge($this->data, [
            'status' => 'pending',
        ]);

        $task = Task::factory()->create();

        $this->actingAs($this->user)->putJson('/api/v1/tasks/'.$task->id, $taskData)
            ->assertForbidden();
        
        $this->assertNotEquals($task->fresh()->title, $taskData['title']);
    }

    public function test_a_user_with_the_update_task_permission_can_access_the_update_task_endpoint()
    {
        $taskData = array_merge($this->data, [
            'status' => 'pending',
            'user_id' => $this->employee->id,
        ]);

        $user = User::factory()->create()->assignRole('Employee');
        $task = Task::factory()->for($user, 'author')->create();

        $this->actingAs($this->employee)->putJson('/api/v1/tasks/'.$task->id, $taskData)
            ->assertOk()
            ->assertJsonFragment([
                'id' => $task->id,
                'title' => $taskData['title'],
                'description' => $taskData['description'],
                'status' => $taskData['status'],
                'author' => $user->name,
                'user' => $this->employee->name,
                'updated_at' => [
                    'human' => $task->updated_at->diffForHumans(),
                    'date_time' => $task->updated_at->toDateTimeString(),
                ]
            ])->assertJsonFragment([
                'id' => $task->project->id,
                'title' => $task->project->title,
                'description' => $task->project->description,
                'due_date' => $task->project->due_date->format('d M Y'),
            ]);
    }

    public function test_a_guest_cannot_access_the_destroy_task_endpoint()
    {
        $task = Task::factory()->create();

        $this->deleteJson('/api/v1/tasks/'.$task->id)
            ->assertUnauthorized();

        $this->assertEquals(0, Task::onlyTrashed()->count());
    }

    public function test_a_user_without_the_delete_task_permission_cannot_access_the_destroy_task_endpoint()
    {
        $task = Task::factory()->create();

        $this->actingAs($this->user)->deleteJson('/api/v1/tasks/'.$task->id)
            ->assertForbidden();

        $this->assertEquals(0, Task::onlyTrashed()->count());
    }

    public function test_a_user_with_the_destroy_task_permission_can_access_the_destroy_task_endpoint()
    {
        $task = Task::factory()->create();

        $this->actingAs($this->employee)->deleteJson('/api/v1/tasks/'.$task->id)
            ->assertNoContent();
        
        $this->assertEquals(1, Task::onlyTrashed()->count());
    }

    public function test_a_guest_cannot_access_the_trashed_tasks_endpoint()
    {
        $this->getJson('/api/v1/trashed/tasks')
            ->assertUnauthorized();
    }

    public function test_a_user_without_the_restore_task_permission_cannot_access_the_trashed_tasks_endpoint()
    {
        $this->actingAs($this->user)->getJson('/api/v1/trashed/tasks')
            ->assertForbidden();
    }

    public function test_an_empty_array_is_returned_when_a_user_with_the_restore_task_permission_accesses_the_trashed_tasks_endpoint_whilst_there_are_no_trashed_tasks()
    {
        $this->actingAs($this->manager)->get('/api/v1/trashed/tasks')
            ->assertOk()
            ->assertJsonFragment([
                'data' => [],
            ]);
    }

    public function test_the_trashed_tasks_are_returned_when_a_user_with_the_restore_task_permission_accesses_the_trashed_tasks_endpoint()
    {
        $task = Task::factory()->trashed()->create([
            'author_id' => $this->manager->id,
            'user_id' => $this->employee->id,
        ]);

        $activeTask = Task::factory()->create();

        $this->actingAs($this->manager)->get('/api/v1/trashed/tasks')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'id' => $task->id,
                'title' => $task->title,
                'status' => 'closed',
                'author' => $task->author->name,
                'user' => $task->user->name,
                'deleted_at' => [
                    'human' => $task->deleted_at->diffForHumans(),
                    'date_time' => $task->deleted_at->toDateTimeString(),
                ],
            ])->assertJsonMissing($activeTask->toArray());

        $this->assertEquals(1, Task::onlyTrashed()->count());
    }

    public function test_a_guest_cannot_access_the_restore_task_endpoint()
    {
        $task = Task::factory()->trashed()->create();

        $this->putJson('/api/v1/tasks/'.$task->id.'/restore')
            ->assertUnauthorized();

    }

    public function test_a_user_without_the_restore_task_permission_cannot_access_the_restore_task_endpoint()
    {
        $task = Task::factory()->trashed()->create();

        $this->actingAs($this->user)->putJson('/api/v1/tasks/'.$task->id.'/restore')
            ->assertForbidden();
    }

    public function test_a_user_with_the_restore_task_permission_can_access_the_restore_task_endpoint()
    {
        $task = Task::factory()->trashed()->create();

        $this->actingAs($this->manager)->putJson('/api/v1/tasks/'.$task->id.'/restore')
            ->assertOk()
                ->assertJsonFragment([
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'status' => 'restored',
                'author' => $task->author->name,
                'user' => $task->user->name,
                'updated_at' => [
                    'human' => $task->fresh()->updated_at->diffForHumans(),
                    'date_time' => $task->fresh()->updated_at->toDateTimeString(),
                ],
            ]);

    }

    public function test_a_guest_cannot_access_the_admin_tasks_endpoint()
    {
        $this->getJson('/api/v1/admin/tasks')
            ->assertUnauthorized();
    }

    public function test_a_user_without_the_admin_tasks_policy_cannot_access_the_admin_tasks_endpoint()
    {
        $this->actingAs($this->user)->getJson('/api/v1/admin/tasks')
            ->assertForbidden();
    }

    public function test_an_empty_array_is_returned_when_a_user_with_the_admin_tasks_policy_accesses_the_admin_tasks_endpoint_whilst_there_are_no_active_tasks()
    {
        $this->actingAs($this->admin)->getJson('/api/v1/admin/tasks')
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonFragment([
                'data' => [],
            ]);
    }

    public function test_a_user_with_the_admin_tasks_policy_can_access_the_admin_tasks_endpoint()
    {
        $task = Task::factory()->create();
        $secondTask = Task::factory()->create();

        $response = $this->actingAs($this->admin)->getJson('/api/v1/admin/tasks');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $secondTask->id)
            ->assertJsonPath('data.1.id', $task->id);
    }
}
