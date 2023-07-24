<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ProjectTaskTest extends TestCase
{
    use RefreshDatabase;

    protected $project, $task;

    public function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create(['manager_id' => $this->manager->id]);
        $this->task = Task::factory()->for($this->project)->create(['user_id' => $this->employee->id]);
    }

    public function test_when_a_new_task_get_created_the_timestamp_of_the_related_project_gets_updated()
    {
        $data = [
            'title' => 'The title for a new task',
            'description' => 'A new sample description',
            'user_id' => $this->employee->id,
        ];

        sleep(1);

        $this->actingAs($this->employee)->post(route('tasks.store', $this->project), $data)
            ->assertRedirect(route('projects.show', $this->project))
            ->assertSessionHas('success', 'A new task has been created.');

        $task = Task::where('title', $data['title'])->first();

        $this->assertEquals($this->project->fresh()->updated_at->format('d-m-Y H:i:s'), $task->updated_at->format('d-m-Y H:i:s'));
    }

    public function test_when_a_task_gets_updated_the_timestamp_of_the_related_project_gets_updated()
    {
        $data = [
            'title' => 'Updated title',
            'user_id' => $this->employee->id,
            'status' => 'pending',
        ];

        $projectTime = $this->project->updated_at;
        sleep(1);

        $this->actingAs($this->employee)->put(route('tasks.update', $this->task), $data)
            ->assertRedirect(route('tasks.show', $this->task))
            ->assertSessionHas('success', 'The task '.$this->task->fresh()->title  .' has been updated.');

        $this->assertTrue($this->project->fresh()->updated_at->gte($projectTime));
        $this->assertEquals($this->project->fresh()->updated_at->format('d-m-Y H:i:s'), $this->task->fresh()->updated_at->format('d-m-Y H:i:s'));
    }

    public function test_when_a_project_gets_deleted_all_related_task_should_also_be_deleted()
    {
        $task = Task::factory()->for($this->project)->create();

        $this->actingAs($this->admin)->delete(route('projects.destroy', $this->project))
            ->assertRedirect(route('projects.index'))
            ->assertSessionHas('success', 'The project has been deleted.');

        $this->assertSoftDeleted($this->project);
        $this->assertSoftDeleted($this->task);
        $this->assertSoftDeleted($task);

        $this->assertTrue($this->task->fresh()->status == 'closed');
        $this->assertTrue($task->fresh()->status == 'closed');
    }

    public function test_only_task_that_were_deleted_before_the_project_was_trashed_will_not_be_restored_when_the_project_gets_restored()
    {
        $project = Project::factory()->create(['created_at' => now()->subDays(7)]);
        $trashedTask = Task::factory()->for($project)->trashed()->create(['deleted_at' =>now()->subMinute()]);
        $task = Task::factory()->for($project)->create();

        $this->actingAs($this->manager)->delete(route('projects.destroy', $project))
            ->assertRedirect(route('projects.index'));

        $this->assertSoftDeleted($project);
        $this->assertSoftDeleted($task);

        $this->actingAs($this->admin)->patch(route('projects.restore', $project))
            ->assertRedirect(route('projects.trashed'));

        $this->assertNotSoftDeleted($project);
        $this->assertTrue($project->fresh()->status == 'restored');

        $this->assertNotSoftDeleted($task);
        $this->assertTrue($task->fresh()->status == 'restored');

        $this->assertSoftDeleted($trashedTask->fresh());
        $this->assertFalse($trashedTask->fresh()->status == 'restored');
    }
}
