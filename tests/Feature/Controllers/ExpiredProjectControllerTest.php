<?php

namespace Tests\Feature\Controllers;

use App\Models\Project;
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

    public function test_a_user_with_the_read_expired_project_permission_can_visit_the_expired_project_overview_and_will_see_the_truncated_titles_of_the_projects()
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
}
