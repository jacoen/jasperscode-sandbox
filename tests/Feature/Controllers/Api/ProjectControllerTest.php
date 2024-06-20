<?php

namespace Tests\Feature\Controllers\Api;

use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Validation\UnauthorizedException;
use Tests\TestCase;

class ProjectControllerTest extends TestCase
{
    use RefreshDatabase;

    protected array $data;

    public function setUp(): void
    {
        parent::setUp();

        $this->data = [
            'title' => 'Some title',
            'description' => 'Please enter your description here',
            'due_date' => now()->addMonths(6)->format('Y-m-d'),
        ];
    }

    public function test_a_guest_is_not_authorized_to_access_the_projects_overview_endpoint()
    {
        $this->getJson('/api/v1/projects')
         ->assertUnauthorized();
    }

    public function test_a_user_without_the_read_projects_permission_is_not_authorized_to_visit_the_projects_overview_endpoint()
    {
        $this->actingAs($this->user)->getJson('api/v1/projects')
            ->assertForbidden();
    }

    public function test_an_empty_array_is_returned_when_a_user_with_the_read_projects_permission_visits_the_project_overview_endpoint_whilst_there_are_no_project()
    {
        $this->actingAs($this->employee)->getJson('/api/v1/projects')
            ->assertOk()
            ->assertJsonFragment([
                'data' => [],
            ]);
    }

    public function test_the_active_project_are_returned_when_a_user_with_the_read_projects_permission_visit_the_project_overview_endpoint()
    {
        $project = Project::factory()->create($this->data);
        Project::factory()->create();

        $this->actingAs($this->employee)->getJson('/api/v1/projects')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'id' => $project->id,
                'title' => $project->title,
                'due_date' => $project->due_date->format('d M Y'),
            ]);
    }

    public function test_a_valid_manager_id_must_be_provided_when_creating_a_project()
    {
        $data = array_merge($this->data, [
            'manager_id' => 999,
        ]);

        $this->invalidManagerValidation('post', '/api/v1/projects', $data);

        $this->assertEquals(0, Project::count());
    }

    public function test_the_title_and_due_date_fields_are_required_when_creating_a_project()
    {
        $data = [
            'title' => '',
            'due_date' => '',
        ];

        $this->actingAs($this->manager)->postJson('/api/v1/projects', $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title' => 'The title field is required.',
                'due_date' => 'The due date field is required.'
            ]);

        $this->assertEquals(0, Project::count());
    }

    public function test_the_provided_title_and_description_must_be_at_least_3_characters_long()
    {
        $data = array_merge($this->data, [
            'title' => 'ab',
            'description' => 'ab',
        ]);

        $this->minimumLengthValidation('post', '/api/v1/projects', $data);

        $this->assertEquals(0, Project::count());
    }

    public function test_the_provided_title_and_description_cannot_be_longer_than_255_characters()
    {
        $data = array_merge($this->data, [
            'title' => str_repeat('abc', 90),
            'description' => str_repeat('abc', 90),
        ]);

        $this->maximumLengthValidation('post', 'api/v1/projects', $data);

        $this->assertEquals(0, Project::count());
    }

    public function test_the_provided_due_date_must_be_greater_than_the_current_date_when_creating_a_project()
    {
        $data = array_merge($this->data, [
            'due_date' => now()->format('Y-m-d'),
        ]);

        $this->dateAfterValidation('POST', 'api/v1/projects', $data);

        $this->assertEquals(0, Project::count());
    }

    public function test_the_provided_due_date_must_be_before_january_first_2031_when_creating_a_project()
    {
        $data = array_merge($this->data, [
            'due_date' => Carbon::create(2031, 1, 1),
        ]);

        $this->dateBeforeValidation('POST', 'api/v1/projects', $data);

        $this->assertEquals(0, Project::count());

    }

    public function test_a_guest_cannot_access_the_create_project_endpoint()
    {
        $this->postJson('/api/v1/projects', $this->data)
            ->assertUnauthorized();

        $this->assertEquals(0, Project::count());
    }

    public function test_a_user_without_the_create_project_permission_cannot_access_the_create_project_endpoint()
    {
        $this->actingAs($this->employee)->postJson('api/v1/projects', $this->data)
            ->assertForbidden();

        $this->assertEquals(0, Project::count());
    }

    public function test_a_user_with_the_create_project_permission_can_access_the_create_project_end_point()
    {
        $this->actingAs($this->manager)->postJson('api/v1/projects', $this->data)
            ->assertCreated()
            ->assertJsonFragment([
                'title' => $this->data['title'],
                'description' => $this->data['description'],
                'due_date' => Carbon::parse($this->data['due_date'])->format('d M Y'),
            ]);

        $this->assertEquals(1, Project::count());
    }

    public function test_a_guest_cannot_access_the_project_detail_endpoint()
    {
        $project = Project::factory()->create();

        $this->getJson('/api/v1/projects/'.$project->id)
            ->assertUnauthorized();
    }

    public function test_a_user_without_the_read_project_permission_cannot_access_the_project_detail_endpoint()
    {
        $project = Project::factory()->create();

        $this->actingAs($this->user)->getJson('/api/v1/projects/'.$project->id)
            ->assertForbidden();
    }

    public function test_a_valid_manager_id_must_be_provided_when_updating_a_project()
    {
        $data = array_merge([
            'manager_id' => 999,
        ]);

        $project = Project::factory()->create(['manager_id' => $this->manager->id]);
        
        $this->invalidManagerValidation('PUT', '/api/v1/projects/'.$project->id, $data); 

        $this->assertNotEquals($project->fresh()->manager_id, $data['manager_id']);
    }

    public function test_the_title_due_date_and_status_fields_are_required_when_updating_a_project()
    {
        $data = [
            'title' => '',
            'due_date' => '',
            'status' => '',
        ];

        $project = Project::factory()->create(['manager_id' => $this->manager->id]);

        $this->actingAs($this->manager)->putJson('/api/v1/projects/'.$project->id, $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title',
                'due_date',
                'status',
            ]);
    }

    public function test_the_provided_title_and_description_must_be_at_least_3_characters_long_when_updating_a_project()
    {
        $data = array_merge($this->data, [
            'title' => 'ab',
            'description' => 'ab',
            'status' => 'pending'
        ]);

        $project = Project::factory()->create();
        
        $this->minimumLengthValidation('PUT', '/api/v1/projects/'.$project->id, $data);

        $this->assertNotEquals($project->fresh()->title, $data['title']);
        $this->assertNotEquals($project->fresh()->description, $data['description']);
    }

    public function test_the_provided_title_and_description_cannot_be_longer_than_255_characters_when_updating_a_project()
    {
        $data = array_merge($this->data, [
            'title' => str_repeat('abc', 90),
            'description' => str_repeat('abc', 90),
            'status' => 'pending',
        ]);

        $project = Project::factory()->create();

        $this->maximumLengthValidation('PUT', '/api/v1/projects/'.$project->id, $data);

        $this->assertNotEquals($project->fresh()->title, $data['title']);
        $this->assertNotEquals($project->fresh()->description, $data['description']);
    }

    public function test_the_provided_due_date_must_be_a_date_after_the_current_date_when_updating_a_project()
    {
        $data = array_merge($this->data, [
            'due_date' => now()->format('d-m-Y'),
            'status' => 'pending',
        ]);

        $project = Project::factory()->create(['due_date' => now()->addMonth()]);

        $this->dateAfterValidation('PUT', 'api/v1/projects/'.$project->id, $data);

        $this->assertNotEquals($project->fresh()->due_date->format('d-m-Y'), $data['due_date']);
        $this->assertTrue($project->fresh()->due_date->gt($data['due_date']));
    }

    public function test_the_provided_due_date_must_be_before_january_first_2031_when_updating_a_project()
    {
        $data = array_merge($this->data, [
            'due_date' => Carbon::create(2031, 1, 1),
            'status' => 'pending',
        ]);

        $project = Project::factory()->create();

        $this->dateBeforeValidation('PUT', 'api/v1/projects/'.$project->id, $data);

        $this->assertNotEquals($project->fresh()->due_date->format('d-m-Y'), $data['due_date']);
        $this->assertTrue($project->fresh()->due_date->lt($data['due_date']));
    }

    public function test_a_guest_cannot_access_the_update_project_endpoint()
    {
        $data = [
            'title' => 'Another title',
            'description' => 'The description has been changed.',
            'status' => 'pending',
            'due_date' => now()->addMonths(4)->format('d-m-Y'),
            'manager_id' => $this->manager->id,
        ];

        $project = Project::factory()->create(['due_date' => now()->addMonths(6)]);

        $this->putJson('/api/v1/projects/'.$project->id, $data)
            ->assertUnauthorized();

        $this->assertNotEquals($project->fresh()->title, $data['title']);
        $this->assertNotEquals($project->fresh()->description, $data['description']);
        $this->assertNotEquals($project->fresh()->status, $data['status']);
        $this->assertNotEquals($project->fresh()->due_date->format('d-m-Y'), $data['due_date']);
        $this->assertNotEquals($project->fresh()->manager_id, $data['manager_id']);
    }

    public function test_a_user_without_the_update_project_permission_cannot_access_the_update_project_endpoint()
    {
        $data = [
            'title' => 'Another title',
            'description' => 'The description has been changed.',
            'status' => 'pending',
            'due_date' => now()->addMonths(4)->format('d-m-Y'),
            'manager_id' => $this->manager->id,
        ];

        $project = Project::factory()->create(['due_date' => now()->addMonths(6)]);

        $this->actingAs($this->employee)->putJson('/api/v1/projects/'.$project->id, $data)
            ->assertForbidden();

        $this->assertNotEquals($project->fresh()->title, $data['title']);
        $this->assertNotEquals($project->fresh()->description, $data['description']);
        $this->assertNotEquals($project->fresh()->status, $data['status']);
        $this->assertNotEquals($project->fresh()->due_date->format('d-m-Y'), $data['due_date']);
        $this->assertNotEquals($project->fresh()->manager_id, $data['manager_id']);
    }

    public function test_a_user_with_the_update_project_permission_can_access_the_update_project_endpoint()
    {
        $data = [
            'title' => 'Another title',
            'description' => 'The description has been changed.',
            'status' => 'pending',
            'due_date' => now()->addMonths(4)->format('d-m-Y'),
            'manager_id' => $this->manager->id,
        ];

        $project = Project::factory()->create(['due_date' => now()->addMonths(6)]);

        $this->actingAs($this->manager)->putJson('/api/v1/projects/'.$project->id, $data)
            ->assertOk();

        $this->assertEquals($project->fresh()->title, $data['title']);
        $this->assertEquals($project->fresh()->description, $data['description']);
        $this->assertEquals($project->fresh()->status, $data['status']);
        $this->assertEquals($project->fresh()->due_date->format('d-m-Y'), $data['due_date']);
        $this->assertEquals($project->fresh()->manager_id, $data['manager_id']);
    }

    public function test_a_user_without_the_pin_project_permission_cannot_pin_a_project()
    {
        $data = [
            'title' => 'Another title',
            'description' => 'The description has been changed.',
            'status' => 'pending',
            'due_date' => now()->addMonths(4)->format('d-m-Y'),
            'manager_id' => $this->manager->id,
            'is_pinned' => true,
        ];

        $project = Project::factory()->create(['due_date' => now()->addMonths(6)]);

        $this->actingAs($this->manager)->putJson('/api/v1/projects/'.$project->id, $data)
            ->assertForbidden()
            ->assertJsonFragment([
                'error' => 'Not authorized',
                'message' => 'You are not authorized to pin a project.',
            ]);

        $this->assertNotEquals($project->fresh()->is_pinned, $data['is_pinned']);
    }

    public function test_an_admin_cannot_pin_another_project_when_a_project_has_already_been_pinned()
    {
        $data = [
            'title' => 'Another title',
            'description' => 'The description has been changed.',
            'status' => 'pending',
            'due_date' => now()->addMonths(4)->format('d-m-Y'),
            'manager_id' => $this->manager->id,
            'is_pinned' => true,
        ];

        Project::factory()->create([
            'due_date' => now()->addMonths(4),
            'is_pinned' => true,
        ]);

        $project = Project::factory()->create(['due_date' => now()->addMonths(6)]);

        $this->actingAs($this->admin)->putJson('/api/v1/projects/'.$project->id, $data)
            ->assertUnprocessable()
            ->assertJsonFragment([
                'error' => 'Invalid pinned project',
                'message' => 'There is a pinned project already. If you want to pin this project you will have to unpin the other project.',
            ]);

        $this->assertNotEquals($project->fresh()->is_pinned, $data['is_pinned']);
    }

    public function test_a_guest_cannot_access_the_destroy_project_endpoint()
    {
        $project = Project::factory()->create();

        $this->deleteJson('/api/v1/projects/'.$project->id)
            ->assertUnauthorized();

        $this->assertEquals(1, Project::count());
    }

    public function test_a_user_without_the_delete_project_permission_cannot_access_the_delete_project_endpoint()
    {
        $project = Project::factory()->create();

        $this->actingAs($this->user)->deleteJson('/api/v1/projects/'.$project->id)
            ->assertForbidden();

        $this->assertEquals(1, Project::count());
    }

    public function test_a_user_with_the_delete_project_permission_can_access_the_delete_project_endpoint()
    {
        $project = Project::factory()->create();

        $this->actingAs($this->manager)->deleteJson('/api/v1/projects/'.$project->id)
            ->assertNoContent();

        $this->assertEquals(0, Project::count());
    }

    public function test_a_user_with_the_delete_project_permission_cannot_delete_a_pinned_project()
    {
        $project = Project::factory()->create(['is_pinned' => true]);

        $this->actingAs($this->manager)->deleteJson('/api/v1/projects/'.$project->id)
            ->assertForbidden()
            ->assertJsonFragment([
                'error' => 'Unable to delete project.',
                'message' => 'Cannot delete a pinned project.'
            ]);

        $this->assertEquals(1, Project::count());  
    }

    public function test_a_guest_cannot_access_the_trashed_projects_endpoint()
    {
        $this->getJson('/api/v1/trashed/projects')
            ->assertUnauthorized();
    }

    public function test_a_user_without_the_restore_project_permission_cannot_access_the_trashed_project_endpoint()
    {
        $this->actingAs($this->user)->getJson('/api/v1/trashed/projects')
            ->assertForbidden();
    }

    public function test_an_empty_is_returned_when_a_user_with_the_restore_project_permission_accesses_the_trashed_project_endpoint_whilst_there_are_no_thrashed_projects()
    {
        $this->actingAs($this->manager)->getJson('/api/v1/trashed/projects')
            ->assertOk()
            ->assertJsonFragment([
                'data' => [],
            ]);
    }

    public function test_the_trashed_projects_are_displayed_when_a_user_with_the_restore_project_permission_accesses_the_trashed_projects_endpoint()
    {
        $project = Project::factory()->trashed()->create();
        $activeProject = Project::factory()->create();

        $this->actingAs($this->manager)->getJson('/api/v1/trashed/projects')
            ->assertOk()
            ->assertJsonFragment([
                'title' => $project->title,
                'status' => 'closed',
                'due_date' => $project->due_date->format('d M Y'),
            ])->assertJsonMissing([
                'title' => $activeProject->title,
                'status' => $activeProject->status,
            ]);
    }

    public function test_a_guest_cannot_access_the_restore_project_endpoint()
    {
        $project = Project::factory()->trashed()->create();

        $this->putJson('/api/v1/projects/'.$project->id.'/restore')
            ->assertUnauthorized();

        $this->assertSoftDeleted($project);
    }

    public function test_a_user_without_the_restore_project_permission_cannot_access_the_restore_project_endpoint()
    {
        $project = Project::factory()->trashed()->create();
        
        $this->actingAs($this->user)->putJson('/api/v1/projects/'.$project->id.'/restore')
            ->assertForbidden();

        $this->assertSoftDeleted($project);
    }

    public function test_a_user_with_the_restore_project_permission_can_access_the_restore_project_endpoint()
    {
        $project = Project::factory()->trashed()->create();
        
        $this->actingAs($this->manager)->putJson('/api/v1/projects/'.$project->id.'/restore')
            ->assertOk();

        $project->refresh();
        $this->assertNotSoftDeleted($project);
        $this->assertNull($project->deleted_at);
        $this->assertEquals($project->status, 'restored');
    }

    public function test_a_user_with_the_restore_project_permission_cannot_restore_a_project_the_has_not_been_trashed()
    {
        $project = Project::factory()->create();

        $this->actingAs($this->manager)->putJson('/api/v1/projects/'.$project->id.'/restore')
            ->assertUnprocessable()
            ->assertJsonFragment([
                'message' => 'Project has not been deleted.',
            ]);
    }

    protected function invalidManagerValidation(string $method, string $route, array $data)
    {
        $this->actingAs($this->manager)->json($method, $route, $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'manager_id' => 'The selected manager is invalid.',
            ]);
    }

    protected function minimumLengthValidation(string $method, string $route, array$data)
    {
        $this->actingAs($this->manager)->json($method, $route, $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title' => 'The title field must be at least 3 characters.',
                'description' => 'The description field must be at least 3 characters.',
            ]);
    }

    protected function maximumLengthValidation(string $method, string $route, array $data)
    {
        $this->actingAs($this->manager)->json($method, $route, $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title' => 'The title field must not be greater than 255 characters.',
                'description' => 'The description field must not be greater than 255 characters.',
            ]);
    }

    protected function dateAfterValidation(string $method, string $route, array $data)
    {
        $this->actingAs($this->manager)->json($method, $route, $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'due_date' => 'The due date field must be a date after today.'
            ]);
    }

    protected function dateBeforeValidation(string $method, string $route, array $data)
    {
        $this->actingAs($this->manager)->json($method, $route, $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'due_date' => 'The due date field must be a date before 2030-12-31.'
            ]);
        }
}
