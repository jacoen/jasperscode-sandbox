<?php

namespace Tests\Feature\Services;

use App\Exceptions\CannotDeletePinnedProjectException;
use App\Exceptions\InvalidPinnedProjectException;
use App\Exceptions\UnauthorizedPinException;
use App\Models\Project;
use App\Models\User;
use App\Notifications\ProjectAssignedNotification;
use App\Services\ProjectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProjectServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $projectService;

    public function setUp():void
    {
        parent::setUp();

        $this->projectService = app(ProjectService::class);

        Notification::fake();
    } 

    public function test_it_lists_all_active_projects()
    {
        $this->actingAs($this->manager);

        Project::factory(10)->create();
        $project = Project::factory()->create(['title' => 'A special project']);

        $projects = $this->projectService->listProjects();

        $this->assertInstanceOf(LengthAwarePaginator::class, $projects);

        $this->assertCount(11, $projects);
        $this->assertTrue($projects->contains($project));
    }

    public function test_it_can_search_projects_by_title()
    {
        $this->actingAs($this->manager);

        $stockProject = Project::factory()->create();
        $project = Project::factory()->create(['title' => 'A very special title for this project']);

        $projects = $this->projectService->listProjects('special');

        $this->assertInstanceOf(LengthAwarePaginator::class, $projects);

        $this->assertCount(1, $projects);
        $this->assertTrue($projects->contains($project));
        $this->assertFalse($projects->contains($stockProject));
    }

    public function test_it_can_filter_projects_by_status()
    {
        $this->actingAs($this->manager);

        $openProject = Project::factory()->create(['status' => 'open']);
        $pendingProject = Project::factory()->create(['status' => 'pending']);
        $closedProject = Project::factory()->create(['status' => 'closed']);
        $completedProject = Project::factory()->create(['status' => 'completed']);

        $projects = $this->projectService->listProjects('', 'completed');

        $this->assertInstanceOf(LengthAwarePaginator::class, $projects);

        $this->assertCount(1, $projects);
        $this->assertTrue($projects->contains($completedProject));
        $this->assertFalse($projects->contains($openProject));
        $this->assertFalse($projects->contains($pendingProject));
        $this->assertFalse($projects->contains($closedProject));
    }

    public function test_it_lists_the_pinned_project_first_if_an_admin_is_signed_in()
    {
        $this->actingAs($this->admin);

        Project::factory(4)->create(['manager_id' => $this->manager->id]);
        $pinnedProject = Project::factory()->create(['manager_id' => $this->manager->id, 'is_pinned' => true]);
        Project::factory()->create(['manager_id' => $this->manager->id]);

        $projects = $this->projectService->listProjects();

        $this->assertInstanceOf(LengthAwarePaginator::class, $projects);
        $this->assertCount(6, $projects);

        $this->assertEquals($pinnedProject->id, $projects->first()->id);
    }

    public function test_it_can_create_a_new_project()
    {
        $data = [
            'title' => 'Some new title',
            'description' => 'Here is some description for the test',
            'due_date' => now()->addMonths(3)->format('Y-m-d'),
        ];

        $project = $this->projectService->storeProject($data);

        $this->assertInstanceOf(Project::class, $project);
        $this->assertEquals($project->fresh()->status, 'open');

        $this->assertDatabaseHas('projects', $data);
    }

    public function test_it_sends_a_notification_when_the_newly_created_project_has_been_assigned_to_a_manager()
    {
        $data = [
            'title' => 'Some new title',
            'description' => 'Here is some description for the test',
            'due_date' => now()->addMonths(3),
            'manager_id' => $this->manager->id,
        ];

        $this->projectService->storeProject($data);

        Notification::assertSentTo($this->manager, ProjectAssignedNotification::class);
    }

    public function test_it_throws_an_exception_when_a_project_is_pinned_by_a_user_without_the_pin_project_permission_when_updating_a_project()
    {
        $this->actingAs($this->manager);
        $project = Project::factory()->create();

        $data = [
            'title' => 'A new title',
            'description' => 'A new description',
            'is_pinned' => true,
        ];

        $this->expectException(UnauthorizedPinException::class);
        $this->expectExceptionMessage('User is not authorized to pin a project');
        $this->projectService->updateproject($project, $data);

        $this->assertNotEquals($project->fresh()->title, $data['title']);
        $this->assertFalse($project->fresh()->is_pinned);

        $this->assertDatabaseMissing('projects', array_merge($data, ['id' => $project->id]));
    }

    public function test_it_throws_an_exception_when_a_project_gets_pinned_if_there_already_is_a_pinned_project_when_updating_a_project()
    {
        $this->actingAs($this->admin);
        $project = Project::factory()->create();
        Project::factory()->create(['is_pinned' => true]);

        $data = [
            'title' => 'A new title',
            'description' => 'A new description',
            'is_pinned' => true,
        ];

        $this->expectException(InvalidPinnedProjectException::class);
        $this->expectExceptionMessage('There is a pinned project already. If you want to pin this project you will have to unpin the other project.');
        $this->projectService->updateproject($project, $data);

        $this->assertFalse($project->is_pinned);

        $this->assertDatabaseMissing('projects', array_merge($data, ['id' => $project->id]));
    }

    public function test_it_can_update_a_project()
    {
        $this->actingAs($this->manager);
        $project = Project::factory()->create();

        $data = [
            'title' => 'A new title',
            'description' => 'A new description',
            'is_pinned' => false,
        ];

        $this->projectService->updateProject($project, $data);

        $this->assertEquals($project->title, $data['title']);

        $this->assertDatabaseHas('projects', array_merge($data, ['id' => $project->id]));
    }

    public function test_it_notifies_a_user_when_an_existing_project_has_been_reassigned_to_them()
    {
        $this->actingAs($this->manager);
        $newManager = User::factory()->create()->assignRole('Manager');
        $project = Project::factory()->create(['manager_id' => $this->manager->id]);

        $data = [
            'title' => 'A new title',
            'description' => 'A new description',
            'is_pinned' => false,
            'manager_id' => $newManager->id,
        ];

        $this->projectService->updateProject($project, $data);

        Notification::assertSentTo($newManager, ProjectAssignedNotification::class);
        Notification::assertNotSentTo($this->manager, ProjectAssignedNotification::class);
    }

    public function test_it_throws_an_exception_when_destroying_a_pinned_project()
    {
        $project = Project::factory()->create(['is_pinned' => true]);

        $this->expectException(CannotDeletePinnedProjectException::class);
        $this->expectExceptionMessage('Cannot delete a project that is pinned.');
        $this->projectService->destroy($project);

        $this->assertNotSoftDeleted($project);
    }

    public function test_it_can_soft_delete_a_project()
    {
        $project = Project::factory()->create();

        $this->projectService->destroy($project);

        $this->assertSoftDeleted($project);
    }

    public function test_it_can_list_trashed_projects()
    {
        Project::factory(4)->trashed()->create();
        $activeProject = Project::factory()->create();
        $trashedProject = Project::factory()->trashed()->create();
        Project::factory()->create();

        $projects = $this->projectService->listTrashedProjects();

        $this->assertInstanceOf(LengthAwarePaginator::class, $projects);

        $this->assertCount(5, $projects);
        $this->assertTrue($projects->contains($trashedProject));
        $this->assertFalse($projects->contains($activeProject));
    }

    public function test_it_can_list_all_projects_with_an_expired_due_date_that_were_not_completed()
    {
        Project::factory(4)->expiredWithStatus()->create();
        $openExpiredProject = Project::factory()->expiredWithStatus()->create(['status' => 'open']);
        $completedProject = Project::factory()->create([
            'status' => 'completed',
            'due_date' => now()->subDay(),
        ]);
        $activeProject = Project::factory()->create(['due_date' => now()->addMonths(3)]);

        $projects = $this->projectService->listExpiredProjects();

        $this->assertInstanceOf(LengthAwarePaginator::class, $projects);

        $this->assertCount(5, $projects);
        $this->assertTrue($projects->contains($openExpiredProject));
        $this->assertFalse($projects->contains($completedProject));
        $this->assertFalse($projects->contains($activeProject));
    }

    public function test_it_can_filter_the_projects_with_an_expired_due_date_by_year_and_week()
    {
        Project::factory(2)->create([
            'due_date' => '2024-04-17',
            'status' => 'expired',
        ]);
        $expiredProject = Project::factory()->create([
            'due_date' => '2024-04-19',
            'status' => 'expired',
        ]);
        $projectExpiredMarch = Project::factory()->create([
            'due_date' => '2024-03-19',
            'status' => 'expired',
        ]);

        $projects = $this->projectService->listExpiredProjects('2024-16');

        $this->assertCount(3, $projects);
        $this->assertTrue($projects->contains($expiredProject));
        $this->assertFalse($projects->contains($projectExpiredMarch));
    }
}
