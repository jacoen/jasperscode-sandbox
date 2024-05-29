<?php

namespace Tests\Feature\Observers;

use App\Models\Project;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskObserverTest extends TestCase
{
    use RefreshDatabase;

    protected $project;

    protected $task;

    protected $data;

    public function setUp(): void
    {
        parent::setup();

        Carbon::setTestNow(now()->subMinutes(5));

        $this->project = Project::factory()->create();

        $this->task = Task::factory()->for($this->project)->create();

        Carbon::setTestNow();

        $this->data = [
            'title' => 'A title for this test task',
            'description' => 'A small description',
            'user_id' => $this->employee->id,
        ];
    }

    public function test_it_updates_the_updated_at_timestamp_of_the_related_project_when_a_task_gets_created()
    {
        $this->actingAs($this->employee)->post(route('tasks.store', $this->project), $this->data)
            ->assertRedirect(route('projects.show', $this->project));

        $task = Task::where('title', $this->data['title'])->first();

        $this->assertEqualsWithDelta(
            $this->project->fresh()->updated_at,
            $task->created_at,
            1
        );
    }

    public function test_it_does_not_update_the_updated_at_timestamp_of_the_related_project_when_the_status_of_the_task_gets_changed_to_closed()
    {
        $taskData = [
            'title' => 'A new title',
            'description' => $this->task->description,
            'status' => 'closed',
        ];

        $this->task->update($taskData);

        $this->assertEqualsWithDelta($this->task->fresh()->updated_at, now(), 1);

        $this->assertTrue($this->task->fresh()->updated_at > $this->project->fresh()->updated_at);

        $this->assertNotEqualsWithDelta(
            $this->project->fresh()->updated_at,
            $this->task->fresh()->updated_at,
            1
        );
    }

    public function test_it_does_not_update_the_updated_at_timestamp_of_the_related_project_when_the_task_gets_restored()
    {
        $this->assertNotEqualsWithDelta($this->project->updated_at, now(), 1);

        $this->task->delete();
        $this->assertSoftDeleted($this->task);

        $this->task->restore();

        $this->assertTrue($this->task->status == 'restored');
        $this->assertEqualsWithDelta($this->task->updated_at, now(), 1);
        $this->assertNotEqualsWithDelta(
            $this->task->updated_at,
            $this->project->fresh()->updated_at,
            1
        );
    }

    public function test_it_updates_the_updated_at_timestamp_of_the_related_project_when_a_task_gets_updated()
    {
        $taskData = array_merge($this->data, ['status' => 'pending']);

        $this->actingAs($this->employee)->put(route('tasks.update', $this->task), $taskData)
            ->assertRedirect(route('tasks.show', $this->task));

        $this->assertEqualsWithDelta(
            $this->project->updated_at,
            $this->task->updated_at,
            1
        );
    }

    public function test_it_changes_the_status_of_the_task_to_closed_if_the_task_get_soft_deleted()
    {
        $this->actingAs($this->employee)->delete(route('tasks.destroy', $this->task))
            ->assertRedirect(route('projects.show', $this->project));

        $this->assertSoftDeleted($this->task);
        $this->assertTrue($this->task->fresh()->status === 'closed');
    }

    public function test_it_unassigns_the_task_from_the_user_if_the_task_gets_trashed()
    {
        $this->actingAs($this->employee)->delete(route('tasks.destroy', $this->task))
            ->assertRedirect(route('projects.show', $this->project));

        $this->assertSoftDeleted($this->task);
        $this->assertNull($this->task->manager_id);
    }

    public function test_it_changes_the_status_of_the_task_to_restored_if_a_trashed_task_gets_restored()
    {
        $task = Task::factory()->for($this->project)->trashed()->create();

        $task->restore();

        $this->assertNotSoftDeleted($task);

        $this->assertEquals($task->fresh()->status, 'restored');
    }
}
