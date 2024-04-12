<?php

namespace Tests\Feature\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ExpiredProjectControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $project;

    public function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->expiredWithStatus()->create(['manager_id' => $this->manager->id]);
    }

    public function test_a_guest_cannot_visit_the_expired_project_overview()
    {
        $this->get(route('projects.expired'))
            ->assertRedirect(route('login'));
    }

    public function test_a_user_without_the_read_expired_project_permission_cannot_visit_the_expired_project_overview()
    {
        $this->actingAs($this->employee)->get(route('projects.expired'))
            ->assertForbidden();
    }

    public function test_a_user_with_the_read_expired_project_permission_can_visit_the_expired_project_overview()
    {
        $this->actingAs($this->admin)->get(route('projects.expired'))
            ->assertOk()
            ->assertSeeText([
                Str::limit($this->project->title, 55),
                $this->project->manager->name,
                'expired',
                $this->project->due_date->format('d M Y'),
            ]);
    }

    public function test_a_user_with_the_read_expired_project_permission_can_only_see_expired_projects_on_the_expired_project_overview()
    {
        $openProject = Project::factory()->create(['manager_id' => $this->manager->id]);
        $completedProject = Project::factory()->create(['manager_id' => $this->manager->id]);
        $trashedProject = Project::factory()->trashed()->create(['manager_id' => $this->manager->id]);

        $this->actingAs($this->admin)->get(route('projects.expired'))
            ->assertSeeText(Str::limit($this->project->title, 55))
            ->assertDontSeeText([
                $openProject->title,
                $completedProject->title,
                $trashedProject->title
            ]);
    }

    public function test_a_user_with_the_read_expired_project_permission_can_see_a_expired_project_with_the_restored_status_on_the_expired_project_overview()
    {
        $expiredRestoredProject = Project::factory()->expiredWithStatus($days = 1)->create(['deleted_at' => now()->subDays(2)]);
        $expiredRestoredProject->restore();

        $this->actingAs($this->admin)->get(route('projects.expired'))
            ->assertSeeText([
                Str::limit($expiredRestoredProject->title, 55),
                'restored',
                $expiredRestoredProject->due_date->format('d M Y'),
            ]);
    }

    public function test_a_user_with_the_read_expired_project_permission_can_filter_the_expired_projects_by_year_and_week()
    {
        $manager = User::factory()->create()->assignRole('Manager');

        $projectExpiredOneWeekAgo = Project::factory()->create(['status' => 'expired', 'due_date' => now()->subWeek(), 'manager_id' => $manager->id]);
        $projectExpiredTwoWeeksAgo = Project::factory()->create(['status' => 'expired', 'due_date' => now()->subWeeks(2), 'manager_id' => $this->manager->id]);
        $projectExpiredThreeWeeksAgo = Project::factory()->create(['status' => 'expired', 'due_date' => now()->subWeeks(3), 'manager_id' => $manager->id]);

        $yearWeek = now()->year.'-'.now()->subWeeks(2)->weekOfYear; 

        $this->actingAs($this->admin)->get(route('projects.expired', ['yearWeek' => $yearWeek]))
            ->assertSeeText([
                Str::limit($projectExpiredTwoWeeksAgo->title, 55),
                $projectExpiredTwoWeeksAgo->manager->name,
            ])->assertDontSeeText([
                $projectExpiredOneWeekAgo->title,
                $projectExpiredOneWeekAgo->manager->name,
                $projectExpiredThreeWeeksAgo->title,
                $projectExpiredThreeWeeksAgo->manager->name,
            ]);
    }
}