<?php

namespace Tests\Feature\Controllers;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProjectControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $data;

    public function setUp(): void
    {
        parent::setUp();

        $this->data = [
            'title' => 'some title here',
            'description' => 'A small description',
            'due_date' => now()->addMonths(6)->format('Y-m-d'),
        ];
    }

    public function test_a_guest_cannot_visit_the_project_overview_page()
    {
        $this->get(route('projects.index'))
            ->assertRedirect(route('login'));
    }

    public function test_a_user_without_the_read_projects_permission_cannot_visit_the_project_overview_page()
    {
        $this->actingAs($this->user)->get(route('projects.index'))
            ->assertForbidden();
    }

    public function test_a_no_projects_message_will_be_displayed_for_the_user_with_the_read_projects_permission_on_a_empty_projects_list()
    {
        $this->actingAs($this->employee)->get(route('projects.index'))
            ->assertOk()
            ->assertSeeText(['No projects yet.']);
    }

    public function test_a_user_with_the_read_project_permission_can_see_all_active_projects()
    {
        $project = Project::factory()->create(['manager_id' => $this->manager->id]);

        $this->actingAs($this->employee)->get(route('projects.index'))
            ->assertOk()
            ->assertSeeText([
                Str::limit($project->title, 35),
                $this->manager->name,
                $project->due_date->format('d M Y'),
            ]);
    }

    public function test_a_guest_cannot_visit_the_create_project_page()
    {
        $this->get(route('projects.create'))
            ->assertRedirect(route('login'));
    }

    public function test_a_user_without_the_create_project_permission_cannot_visit_the_create_project_page()
    {
        $this->actingAs($this->employee)->get(route('projects.create'))
            ->assertForbidden();
    }

    public function test_a_user_with_the_create_project_permission_can_visit_the_create_project_page()
    {
        $this->actingAs($this->manager)->get(route('projects.create'))
            ->assertOk();
    }

    public function test_the_title_and_due_date_fields_are_required_when_creating_a_project()
    {
        $data = [
            'title' => '',
            'due_date' => '',
        ];

        $this->actingAs($this->manager)->post(route('projects.store'), $data)
            ->assertSessionHasErrors([
                'title' => 'The title field is required.',
                'due_date' => 'The due date field is required.',
            ]);

        $this->assertEquals(0, Project::count());
    }

    public function test_the_manager_must_exist_in_the_users_table_when_creating_a_project()
    {
        $this->actingAs($this->manager)->post(route('projects.store'), array_merge([
            'manager_id' => 9999
        ]))
        ->assertSessionHasErrors([
            'manager_id' => 'The selected manager is invalid.'
        ]);
    }

    public function test_the_title_field_must_be_at_least_3_characters_when_creating_a_project()
    {
        $this->actingAs($this->manager)->post(route('projects.store'), array_merge($this->data, [
            'title' => 'aa',
        ]))->assertSessionHasErrors([
            'title' => 'The title field must be at least 3 characters.',
        ]);

        $this->assertEquals(0, Project::count());
    }

    public function test_the_title_field_cannot_be_greater_than_255_characters_when_creating_a_project()
    {
        $this->actingAs($this->manager)->post(route('projects.store'), array_merge($this->data, [
            'title' => Str::repeat('abc', 120),
        ]))->assertSessionHasErrors([
            'title' => 'The title field must not be greater than 255 characters.',
        ]);

        $this->assertEquals(0, Project::count());
    }

    public function test_the_description_field_must_be_at_least_3_characters_when_creating_a_project()
    {
        $this->actingAs($this->manager)->post(route('projects.store'), array_merge($this->data, [
            'description' => 'aa',
        ]))->assertSessionHasErrors([
            'description' => 'The description field must be at least 3 characters.',
        ]);
    }

    public function test_the_description_field_cannot_be_greater_than_255_characters_when_creating_a_project()
    {
        $this->actingAs($this->manager)->post(route('projects.store'), array_merge($this->data, [
            'description' => Str::repeat('abc', 120),
        ]))->assertSessionHasErrors([
            'description' => 'The description field must not be greater than 255 characters.',
        ]);
    }
    
    public function test_the_due_date_must_be_after_the_current_date_when_creating_a_project()
    {
        $dateLastMonth = array_merge($this->data, ['due_date' => now()->subMonth()->format('Y-m-d')]);
        $dateNow = array_merge($this->data, ['due_date' => now()->format('Y-m-d')]);
        $dataNextMonth = array_merge($this->data, ['due_date' => now()->addMonth()->format('Y-m-d')]);

        $this->actingAs($this->manager)->post(route('projects.store'), $dateLastMonth)
            ->assertSessionHasErrors(['due_date' => 'The due date field must be a date after today.']);

        $this->actingAs($this->manager)->post(route('projects.store'), $dateNow)
            ->assertSessionHasErrors(['due_date' => 'The due date field must be a date after today.']);

        $this->actingAs($this->manager)->post(route('projects.store'), $dataNextMonth)
            ->assertRedirect(route('projects.index'))
            ->assertSessionHas('success', 'A new project has been created.');

        $project = Project::first();

        $this->assertDatabaseCount('projects', 1);
        $this->assertNotEquals($dateLastMonth['due_date'], $project->due_date->format('Y-m-d'));
        $this->assertNotEquals($dateNow['due_date'], $project->due_date->format('Y-m-d'));
        $this->assertEquals($dataNextMonth['due_date'], $project->due_date->format('Y-m-d'));
    }

    public function test_a_guest_cannot_create_a_project()
    {
        $this->post(route('projects.store'), $this->data)
            ->assertRedirect(route('login'));

        $this->assertEquals(0, Project::count());
    }

    public function test_a_user_without_the_create_project_permission_cannot_create_a_project()
    {
        $this->actingAs($this->employee)->post(route('projects.store'), $this->data)
            ->assertForbidden();

        $this->assertEquals(0, Project::count());
    }

    public function test_a_user_with_the_create_project_permission_can_create_a_project()
    {
        $this->actingAs($this->manager)->post(route('projects.store'), $this->data)
            ->assertRedirect(route('projects.index'))
            ->assertSessionHas('success', 'A new project has been created.');

        $this->assertEquals(1, Project::count());

        $project = Project::first();

        $this->assertEquals($project->title, $this->data['title']);
        $this->assertEquals($project->description, $this->data['description']);
        $this->assertEquals($project->due_date->format('Y-m-d'), $this->data['due_date']);
    }

    public function test_a_guest_cannot_visit_the_project_detail_page()
    {
        $project = Project::factory()->create();

        $this->get(route('projects.show', $project))
            ->assertRedirect(route('login'));
    }

    public function test_a_user_without_the_read_project_permission_cannot_visit_the_project_detail_page()
    {
        $project = Project::factory()->create();

        $this->actingAs($this->user)->get(route('projects.show', $project))
            ->assertForbidden();
    }

    public function test_a_user_with_the_read_project_permission_can_visit_the_project_detail_page()
    {
        $project = Project::factory()->create([
            'manager_id' => $this->manager->id
        ]);
        
        $this->actingAs($this->employee)->get(route('projects.show', $project))
            ->assertOk()
            ->assertSeeText([
                $project->title,
                $project->description,
                $project->manager->name,
                $project->due_date->format('d M Y'),
                'open', // project status
                lastUpdated($project->updated_at),
            ]);
    }

    public function test_a_no_tasks_message_will_be_displayed_when_the_user_visits_the_project_detail_page_of_a_project_without_related_tasks()
    {
        $project = Project::factory()->create([
            'manager_id' => $this->manager->id
        ]);

        $this->actingAs($this->employee)->get(route('projects.show', $project))
            ->assertOk()
            ->assertSeeText('No tasks in this project yet.');
    }

    public function test_the_tasks_related_to_the_project_will_be_displayed_when_the_user_visits_the_project_detail_page_of_a_project_with_tasks()
    {
        $project = Project::factory()->create([
            'manager_id' => $this->manager->id
        ]);

        $task = Task::factory()->for($project)->create([
            'author_id' => $this->employee->id,
            'user_id' => $this->employee->id, 
        ]);

        $this->actingAs($this->employee)->get(route('projects.show', $project))
            ->assertOk()
            ->assertSeeText([
                $task->title,
                $task->author->name,
                $task->user->name,
                $task->status,
            ]);
    }

    public function test_a_guest_cannot_visit_the_edit_project_page()
    {
        $project = Project::factory()->create();

        $this->get(route('projects.edit', $project))
            ->assertRedirect(route('login'));
    }

    public function test_a_user_without_the_update_project_permission_cannot_visit_the_edit_project_page()
    {
        $project = Project::factory()->create();

        $this->actingAs($this->employee)->get(route('projects.edit', $project))
            ->assertForbidden();
    }

    public function test_a_user_with_the_update_project_permission_can_visit_the_edit_project_page()
    {
        $project = Project::factory()->create();

        $this->actingAs($this->manager)->get(route('projects.edit', $project))
            ->assertOk()
            ->assertSeeText($project->title);
    }

    public function test_the_title_due_date_and_status_fields_are_required_when_updating_an_existing_project()
    {
        $data = [
            'title' => '',
            'due_date' =>  '',
            'status' => '',
        ];

        $project = Project::factory()->create();

        $this->actingAs($this->manager)->put(route('projects.update', $project), $data)
            ->assertSessionHasErrors([
                'title' => 'The title field is required.',
                'due_date' => 'The due date field is required.',
                'status' => 'The status field is required.',
            ]);

        $project->refresh();

        $this->assertNotEmpty($project->title);
        $this->assertNotEmpty($project->due_date);
        $this->assertNotEmpty($project->status);
    }

    public function test_a_valid_manager_must_be_provided_when_updating_an_existing_user()
    {
        $project = Project::factory()->create([
            'manager_id' => $this->manager->id,
        ]);

        $this->actingAs($this->manager)->put(route('projects.update', $project), array_merge($this->data, ['manager_id' => 9999]))
            ->assertSessionHasErrors([
                'manager_id' => 'The selected manager is invalid.'
            ]);

        $this->assertNotEquals($project->fresh()->manager->id, 9999);
    }

    public function test_the_provided_title_must_be_at_least_3_characters_long_when_updating_a_project()
    {
        $project = Project::factory()->create();

        $this->actingAs($this->manager)->put(route('projects.update', $project), array_merge($this->data, [
            'title' => 'aa',
        ]))->assertSessionHasErrors([
            'title' => 'The title field must be at least 3 characters.',
        ]);

        $this->assertNotEquals($project->fresh()->title, 'aaa');
    }

    public function test_the_provided_title_cannot_be_longer_than_255_characters_when_updating_a_project()
    {
        $project = Project::factory()->create();

        $this->actingAs($this->manager)->put(route('projects.update', $project), array_merge($this->data, [
            'title' => Str::repeat('abc', 120),
        ]))->assertSessionHasErrors([
            'title' => 'The title field must not be greater than 255 characters.',
        ]);
    }

    public function test_the_provided_due_date_must_be_a_date_when_updating_a_project()
    {
        $project = Project::factory()->create();

        $this->actingAs($this->manager)->put(route('projects.update', $project), array_merge($this->data, [
            'due_date' => 12345,
        ]))->assertSessionHasErrors([
            'due_date' => 'The due date field must be a valid date.'
        ]);

        $this->assertNotEquals($project->due_date, 12345);
    }

    public function test_the_provided_date_must_be_after_the_current_date_when_updating_a_project()
    {
        $project = Project::factory()->create();

        $this->actingAs($this->manager)->put(route('projects.update', $project), array_merge($this->data, [
            'due_date' => now()->format('Y-m-d'),
        ]))->assertSessionHasErrors([
            'due_date' => 'The due date field must be a date after today.'
        ]);

        $this->assertNotEquals($project->due_date->format('Y-m-d'), now()->format('Y-m-d'));
    }

    public function test_the_provided_date_must_be_before_the_end_of_2030_when_updating_a_project()
    {
        $project = Project::factory()->create();

        $this->actingAs($this->manager)->put(route('projects.update', $project), array_merge($this->data, [
            'due_date' => now()->addYears(7)->format('Y-m-d'),
        ]))->assertSessionHasErrors([
            'due_date' => 'The due date field must be a date before 2030-12-31.'
        ]);

        $this->assertNotEquals($project->fresh()->due_date->format('Y-m-d'), now()->addYears(7)->format('Y-m-d'));

    }

    public function test_the_provided_status_must_be_valid()
    {
        $project = Project::factory()->create();

        $this->actingAs($this->manager)->put(route('projects.update', $project), array_merge($this->data, [
            'status' => 'restored',
        ]))->assertSessionHasErrors([
            'status' => 'The selected status is invalid.',
        ]);

        $this->assertNotEquals($project->fresh()->status, 'restored');
    }

    public function test_a_guest_cannot_update_a_project()
    {
        $project = Project::factory()->create();

        $this->put(route('projects.update', $project), array_merge($this->data, [
            'status' => 'pending',
        ]))->assertRedirect(route('login'));

        $project->refresh();
        $this->assertNotEquals($project->title, $this->data['title']);
        $this->assertNotEquals($project->description, $this->data['description']);
        $this->assertNotEquals($project->status, 'pending');
        $this->assertNotEquals($project->due_date->format('Y-m-d'), now()->addMonths(6)->format('Y-m-d'));
    }

    public function test_a_user_without_the_update_project_permission_cannot_update_a_project()
    {
        $project = Project::factory()->create();

        $this->actingAs($this->employee)->put(route('projects.update', $project), array_merge($this->data, [
            'status' => 'pending',
        ]))->assertForbidden();

        $project->refresh();
        $this->assertNotEquals($project->title, $this->data['title']);
        $this->assertNotEquals($project->description, $this->data['description']);
        $this->assertNotEquals($project->status, 'pending');
        $this->assertNotEquals($project->due_date->format('Y-m-d'), now()->addMonths(6)->format('Y-m-d'));
    }

    public function test_a_user_with_the_update_project_permission_can_update_a_project()
    {
        $project = Project::factory()->create();

        $this->actingAs($this->manager)->put(route('projects.update', $project), array_merge($this->data, [
            'status' => 'pending',
        ]))->assertRedirect(route('projects.show', $project))
        ->assertSessionHas('success', 'The project has been updated.');

        $project->refresh();
        $this->assertEquals($project->title, $this->data['title']);
        $this->assertEquals($project->description, $this->data['description']);
        $this->assertEquals($project->status, 'pending');
        $this->assertEquals($project->due_date->format('Y-m-d'), now()->addMonths(6)->format('Y-m-d'));
    }

    public function test_the_user_gets_redirected_to_the_edit_project_page_when_the_unauthorized_pin_exception_occurs_while_updating_a_project()
    {
        $project = Project::factory()->create();

        $this->actingAs($this->manager)->put(route('projects.update', $project), array_merge($this->data, [
            'status' => 'pending',
            'is_pinned' => true,
        ]))->assertRedirect(route('projects.edit', $project))
            ->assertSessionHasErrors([
                'error' => 'You are not authorized to pin a project.',
            ]);
    }

    public function test_the_user_gets_redirected_to_the_edit_project_page_when_invalid_pinned_project_exception_occurs_while_updating_a_project()
    {
        Project::factory()->create(['is_pinned' => true]);
        $project = Project::factory()->create();

        $this->actingAs($this->admin)->put(route('projects.update', $project), array_merge($this->data, [
            'status' => 'pending',
            'is_pinned' => true,
        ]))->assertRedirect(route('projects.edit', $project))
            ->assertSessionHasErrors([
                'error' => 'There is a pinned project already. If you want to pin this project you will have to unpin the other project.'
            ]);
    }

    public function test_a_guest_cannot_delete_a_project()
    {
        $project = Project::factory()->create();

        $this->delete(route('projects.destroy', $project))
            ->assertRedirect(route('login'));

        $this->assertNotSoftDeleted($project);
    }

    public function test_a_user_without_the_delete_project_permission_cannot_delete_a_project()
    {
        $project = Project::factory()->create();

        $this->actingAs($this->employee)->delete(route('projects.destroy', $project))
            ->assertForbidden();

        $this->assertNotSoftDeleted($project);
    }

    public function test_a_user_with_the_delete_project_permission_can_delete_a_project()
    {
        $project = Project::factory()->create();

        $this->actingAs($this->manager)->delete(route('projects.destroy', $project))
            ->assertRedirect(route('projects.index'))
            ->assertSessionHas('success', 'The project has been deleted.');

        $this->assertSoftDeleted($project);
    }

    public function test_the_user_gets_redirected_to_the_projects_index_page_when_the_pinned_project_destruction_exception_occurs_while_deleting_a_project()
    {
        $project = Project::factory()->create(['is_pinned' => true]);

        $this->actingAs($this->manager)->delete(route('projects.destroy', $project))
            ->assertRedirect(route('projects.index'))
            ->assertSessionHasErrors([
                'error' => 'Cannot delete a project that is pinned.',
            ]);
    }

    public function test_a_guest_cannot_visit_the_trashed_projects_page()
    {
        $this->get(route('projects.trashed'))->assertRedirect(route('login'));
    }

    public function test_a_user_without_the_restore_project_permission_cannot_visit_the_trashed_projects_page()
    {
        $this->actingAs($this->employee)->get(route('projects.trashed'))
            ->assertForbidden();
    }

    public function test_a_user_with_the_restore_project_permission_can_visit_the_trashed_projects_page_and_can_see_the_truncated_project_titles()
    {
        $project = Project::factory()->trashed()->create();
        
        $this->actingAs($this->admin)->get(route('projects.trashed'))
            ->assertOk()
            ->assertSeeText([
                Str::limit($project->title, 35),
                $project->due_date->format('d M Y'),
                lastUpdated($project->deleted_at),
            ]);
    }

    public function test_a_guest_cannot_restore_a_project()
    {
        $project = Project::factory()->trashed()->create();

        $this->patch(route('projects.restore', $project))
            ->assertRedirect(route('login'));

        $this->assertSoftDeleted($project);
    }

    public function test_a_user_without_the_restore_project_permission_cannot_restore_a_project()
    {
        $project = Project::factory()->trashed()->create();

        $this->actingAs($this->employee)->patch(route('projects.restore', $project))
            ->assertForbidden();

        $this->assertSoftDeleted($project);
    }

    public function test_a_user_with_the_restore_project_permission_can_restore_a_project()
    {
        $project = Project::factory()->trashed()->create();

        $this->actingAs($this->admin)->patch(route('projects.restore', $project))
            ->assertRedirect(route('projects.trashed'))
            ->assertSessionHas('success', 'The project '.$project->title.' has been restored.');

        $this->assertNotSoftDeleted($project);
    }

    public function test_a_guest_cannot_force_delete_a_project()
    {
        $project = Project::factory()->trashed()->create();

        $this->delete(route('projects.force-delete', $project))
            ->assertRedirect(route('login'));

        $this->assertEquals(1, Project::withTrashed()->count());
    }

    public function test_a_user_without_the_right_permissions_cannot_force_delete_a_project()
    {
        $project = Project::factory()->trashed()->create();
        
        $this->actingAs($this->employee)->delete(route('projects.force-delete', $project))
            ->assertForbidden();

        $this->assertEquals(1, Project::withTrashed()->count());
    }

    public function test_a_user_with_the_right_permissions_can_force_delete_a_project()
    {
        $project = Project::factory()->trashed()->create();

        $this->actingAs($this->admin)->delete(route('projects.force-delete', $project))
            ->assertRedirect(route('projects.trashed'))
            ->assertSessionHas('success', 'The project has been permanently deleted.');

        $this->assertEquals(0, Project::withTrashed()->count());
    }
}
