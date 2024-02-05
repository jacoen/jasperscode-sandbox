<?php

namespace Tests\Feature\Observers;

use App\Models\Project;
use App\Models\Task;
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

        $this->project = Project::factory()->create();

        $this->task = Task::factory()->for($this->project)->create();

        $this->data = [
            'title' => 'A title for this test task',
            'description' => 'A small description',
            'user_id' => $this->employee->id,
        ];
    }

    public function test_when_a_task_gets_created_the_timestamp_of_the_related_project_gets_updated()
    {
        $this->actingAs($this->employee)->post(route('tasks.store', $this->project), $this->data)
            ->assertRedirect(route('projects.show', $this->project));

        $task = Task::where('title', $this->data['title'])->first();

        $this->assertEqualsWithDelta(
            $this->project->updated_at,
            $task->created_at,
            1
        );
    }

    public function test_when_a_project_gets_updated_the_timestamp_of_the_related_project_gets_updated()
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

    public function test_when_a_task_gets_trashed_the_status_gets_changed_to_closed()
    {
        $this->actingAs($this->employee)->delete(route('tasks.destroy', $this->task))
            ->assertRedirect(route('projects.show', $this->project));

        $this->assertSoftDeleted($this->task);
        $this->assertTrue($this->task->fresh()->status === 'closed');
    }

    public function test_when_a_task_gets_trashed_the_task_gets_unassigned_form_the_user()
    {
        $this->actingAs($this->employee)->delete(route('tasks.destroy', $this->task))
            ->assertRedirect(route('projects.show', $this->project));

        $this->assertSoftDeleted($this->task);
        $this->assertNull($this->task->manager_id);
    }
}
