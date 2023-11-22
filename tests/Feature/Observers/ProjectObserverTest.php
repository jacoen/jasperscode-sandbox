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

        $this->actingAs($this->manager)->delete(route('projects.destroy', $this->project))
            ->assertRedirect(route('projects.index'));

        $this->assertSoftDeleted($this->project);
        $this->assertSoftDeleted($task1);
        $this->assertSoftDeleted($task2);
    }

    public function test_when_a_project_has_been_soft_deleted_the_status_of_the_project_gets_changed_to_closed()
    {
        $this->actingAs($this->manager)->delete(route('projects.destroy', $this->project))
            ->assertRedirect(route('projects.index'));

        $this->assertEquals($this->project->fresh()->status, 'closed');
    }

    public function test_when_a_project_has_been_soft_deleted_the_project_gets_unassigned_from_the_manager()
    {
        $this->actingAs($this->manager)->delete(route('projects.destroy', $this->project))
            ->assertRedirect(route('projects.index'));

        $this->assertNull($this->project->fresh()->manager_id);
    }

    public function test_when_a_project_gets_restored_the_status_of_the_project_gets_changed_to_restored()
    {
        $this->actingAs($this->admin)->patch(route('projects.restore', $this->trashedProject))
            ->assertRedirect(route('projects.trashed'));

        $this->assertEquals($this->trashedProject->fresh()->status, 'restored');
    }

    public function test_when_a_project_gets_restored_all_tasks_that_are_deleted_at_the_same_time_as_the_project_also_get_restored()
    {
        $task = Task::factory()->for($this->trashedProject)->trashed()->create();

        $this->actingAs($this->admin)->patch(route('projects.restore', $this->trashedProject))
            ->assertRedirect(route('projects.trashed'));

        $this->assertNotSoftDeleted($this->trashedProject);
        $this->assertNotSoftDeleted($task);
    }

    public function test_when_a_task_is_trashed_before_the_project_gets_soft_deleted_the_task_will_not_get_restored_when_the_project_is_restored()
    {
        $project = Project::factory()->create();
        $task1 = Task::factory()->for($project)->create();
        $task2 = Task::factory()->for($project)->create();

        $this->actingAs($this->admin)->delete(route('tasks.destroy', $task1))
            ->assertRedirect(route('projects.show', $project))
            ->assertDontSeeText($task1->title);

        sleep(1);

        $this->actingAs($this->admin)->delete(route('projects.destroy', $project))
            ->assertRedirect(route('projects.index'));

        $this->actingAs($this->admin)->patch(route('projects.restore', $project->fresh()))
            ->assertRedirect(route('projects.trashed'));

        $this->assertTrue($project->fresh()->status == 'restored');
        $this->assertNotSoftDeleted($project);

        $this->assertTrue($task1->fresh()->status == 'closed');
        $this->assertSoftDeleted($task1);

        $this->assertTrue($task2->fresh()->status == 'restored');
        $this->assertNotSoftDeleted($task2);
    }
}
