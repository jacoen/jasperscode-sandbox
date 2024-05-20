<?php

namespace Tests\Feature\Observers;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectObserverTest extends TestCase
{
    use RefreshDatabase;

    protected $project;

    protected $trashedProject;

    public function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create(['manager_id' => $this->manager->id]);
        $this->trashedProject = Project::factory()->trashed()->create();
    }

    public function test_when_a_project_gets_soft_deleted_all_related_tasks_get_deleted_as_well()
    {
        $task1 = Task::factory()->for($this->project)->create();
        $task2 = Task::factory()->for($this->project)->create();

        $this->project->delete();

        $this->assertSoftDeleted($this->project);
        $this->assertSoftDeleted($task1);
        $this->assertSoftDeleted($task2);
    }

    public function test_when_a_project_has_been_soft_deleted_the_status_of_the_project_gets_changed_to_closed()
    {
        $this->project->delete();

        $this->assertEquals($this->project->fresh()->status, 'closed');
    }

    public function test_when_a_project_has_been_soft_deleted_the_project_gets_unassigned_from_the_manager()
    {
        $this->project->delete();

        $this->assertNull($this->project->fresh()->manager_id);
    }

    public function test_when_a_project_gets_restored_the_status_of_the_project_gets_changed_to_restored()
    {
        $this->assertEquals($this->trashedProject->status, 'closed');

        $this->trashedProject->restore();

        $this->assertEquals($this->trashedProject->fresh()->status, 'restored');
    }

    public function test_when_a_project_is_restored_all_tasks_deleted_at_the_same_or_later_time_are_also_restored()
    {
        $task = Task::factory()->for($this->trashedProject)->create(['user_id' => null]);
        $task->delete();

        $this->assertSoftDeleted($task);
        $this->assertTrue($task->deleted_at >= $this->trashedProject->deleted_at);

        $this->trashedProject->restore();

        $this->assertNotSoftDeleted($this->trashedProject);
        $this->assertNotSoftDeleted($task);
    }

    public function test_when_a_task_is_trashed_before_the_project_gets_soft_deleted_the_task_will_not_get_restored_when_the_project_is_restored()
    {
        $project = Project::factory()->create(['created_at' => now()->subMinutes(4), 'deleted_at' => now()->subMinute()]);
        $task1 = Task::factory()->for($project)->create(['created_at' => now()->subMinutes(3), 'deleted_at' => now()->subMinutes(2)]);
        $task2 = Task::factory()->for($project)->create(['created_at' => now()->subMinutes(3), 'deleted_at' => now()->subMinute()]);

        $project->restore();

        $this->assertNotSoftDeleted($project);
        $this->assertNotSoftDeleted($task2);
        $this->assertSoftDeleted($task1);
    }
}
