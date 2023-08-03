<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Notifications\ProjectAssignedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProjectCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_without_the_read_project_permission_cannot_visit_the_project_overview_page()
    {
        $this->actingAs($this->user)->get(route('projects.index'))
            ->assertForbidden();
    }

    public function test_a_user_with_the_read_project_permission_can_visit_the_project_overview_page()
    {
        $this->actingAs($this->employee)->get(route('projects.index'))
            ->assertOk()
            ->assertSeeText(['No projects yet.']);

        $project = Project::factory()->create(['manager_id' => $this->manager->id]);

        $this->actingAs($this->employee)->get(route('projects.index'))
            ->assertOk()
            ->assertSeeText([
                Str::limit($project->title, 35),
                $this->manager->name,
                $project->due_date->format('d M Y'),
            ]);
    }

    public function a_user_without_the_read_project_permission_cannot_visit_the_project_detail_page()
    {
        $project = Project::factory()->create(['manager_id' => $this->manager->id]);

        $this->actingAs($this->user)->get(route('projects.show', $project))
            ->assertForbidden();
    }

    public function test_a_user_with_the_read_project_permission_can_visit_the_project_detail_page()
    {
        $project = Project::factory()->create(['manager_id' => $this->manager->id]);

        $this->actingAs($this->employee)->get(route('projects.show', $project))
            ->assertOk()
            ->assertSeeText([$project->title, $project->description, $project->due_date->format('d M Y'), $this->manager->name]);
    }

    public function test_a_user_without_the_create_permission_cannot_create_a_new_project()
    {
        $data = [
            'title' => 'Some new title',
            'description' => 'Here is some description for the test',
            'due_date' => now()->addMonths(3),
            'manager_id' => $this->manager->id,
        ];

        $this->actingAs($this->employee)->get(route('users.create'))
            ->assertForbidden();

        $this->actingAs($this->employee)->post(route('users.store'), $data)
            ->assertForbidden();

        $this->assertDatabaseMissing('projects', $data);
    }

    public function test_a_user_with_the_create_project_permission_can_create_a_new_project()
    {
        $data = [
            'title' => 'Some new title',
            'description' => 'Here is some description for the test',
            'due_date' => now()->addMonths(3),
            'manager_id' => $this->manager->id,
        ];

        $this->actingAs($this->manager)->post(route('projects.store'), $data)
            ->assertRedirect(route('projects.index'))
            ->assertSessionHas('success', 'A new project has been created.');
    }

    public function test_a_message_is_sent_when_a_project_is_assigned_to_a_different_manager()
    {
        $manager = User::factory()->create();
        $manager->assignRole('Manager');

        Notification::fake();

        $data = [
            'title' => 'Some new title',
            'description' => 'Here is some description for the test',
            'due_date' => now()->addMonths(3),
            'manager_id' => $this->manager->id,
        ];

        $this->actingAs($this->manager)->post(route('projects.store'), $data)
            ->assertRedirect(route('projects.index'));

        Notification::assertNothingSent();

        $data['manager_id'] = $manager->id;

        $this->actingAs($this->manager)->post(route('projects.store'), $data)
            ->assertRedirect(route('projects.index'));

        Notification::assertSentTo($manager, ProjectAssignedNotification::class);
    }

    public function test_fields_are_required()
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

        $this->assertDatabaseEmpty('projects');
    }

    public function test_the_due_date_must_be_after_the_current_date()
    {
        $projectLastMonth = Project::factory()->make(['manager_id' => $this->manager->id, 'due_date' => now()->subMonth()])->toArray();
        $projectCurrentDate = Project::factory()->make(['manager_id' => $this->manager->id, 'due_date' => now()])->toArray();
        $projectNextMonth = Project::factory()->make(['manager_id' => $this->manager->id, 'due_date' => now()->addMonth()])->toArray();

        $this->actingAs($this->manager)->post(route('projects.store'), $projectLastMonth)
            ->assertSessionHasErrors(['due_date' => 'The due date field must be a date after today.']);

        $this->actingAs($this->manager)->post(route('projects.store'), $projectCurrentDate)
            ->assertSessionHasErrors(['due_date' => 'The due date field must be a date after today.']);

        $this->actingAs($this->manager)->post(route('projects.store'), $projectNextMonth)
            ->assertRedirect(route('projects.index'))
            ->assertSessionHas('success', 'A new project has been created.');

        $this->assertDatabaseCount('projects', 1);
        $this->assertDatabaseHas('projects', ['title' => $projectNextMonth['title']]);
    }

    public function test_a_user_without_the_edit_project_permission_cannot_edit_a_project()
    {
        $project = Project::factory()->create();

        $data = [
            'title' => 'A simple failed updated project',
            'description' => 'This description has not been updated',
        ];

        $this->actingAs($this->employee)->get(route('projects.edit', $project))
            ->assertForbidden();

        $this->actingAs($this->employee)->put(route('projects.update', $project), $data)
            ->assertForbidden();

        $this->assertDatabaseMissing('projects', [
            'id' => $project->id,
            'title' => $data['title'],
            'description' => $data['description'],
        ]);
    }

    public function test_the_status_and_title_fields_are_required_when_editing_a_project()
    {
        $project = Project::factory()->create(['manager_id' => $this->manager->id]);

        $data = [
            'title' => '',
            'due_date' => $project->due_date,
            'status' => '',
        ];

        $this->actingAs($this->manager)->put(route('projects.update', $project), $data)
            ->assertSessionHasErrors([
                'title' => 'The title field is required.',
                'status' => 'The status field is required.',
            ]);

        $this->assertDatabaseMissing('projects', array_merge($data, ['id' => $project->id]));
    }

    public function test_the_status_field_must_contain_a_valid_value_when_editing_a_project()
    {
        $project = Project::factory()->create(['manager_id' => $this->manager->id]);

        $data = [
            'title' => $project->title,
            'due_date' => $project->due_date,
            'status' => 'restored',
        ];

        $this->actingAs($this->manager)->put(route('projects.update', $project), $data)
            ->assertSessionHasErrors([
                'status' => 'The selected status is invalid.',
            ]);

        $this->assertDatabaseMissing('projects', [
            'id' => $project->id,
            'title' => $project->title,
            'status' => $data['status'],
        ]);
    }

    public function test_a_user_with_the_edit_project_permission_can_edit_a_project()
    {
        $project = Project::factory()->create();

        $data = [
            'title' => 'A simple failed updated project',
            'description' => 'This description has not been updated',
            'due_date' => $project->due_date,
            'status' => 'pending',

        ];

        $this->actingAs($this->manager)->get(route('projects.edit', $project))
            ->assertOk()
            ->assertSee([$project->title, $project->description]);

        $this->actingAs($this->manager)->put(route('projects.update', $project), $data)
            ->assertRedirect(route('projects.show', $project))
            ->assertSessionHas('success', 'The project has been updated.');

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'title' => $data['title'],
            'description' => $data['description'],
            'status' => $data['status'],
        ]);
    }

    public function test_a_user_without_the_delete_project_permission_cannot_delete_a_project()
    {
        $project = Project::factory()->create();

        $this->actingAs($this->employee)->delete(route('projects.destroy', $project))
            ->assertForbidden();

        $this->assertNotSoftDeleted('projects', [
            'id' => $project->id,
            'title' => $project->title,
        ]);
    }

    public function test_a_user_with_the_delete_project_permission_can_delete_a_project()
    {
        $project = Project::factory()->create();

        $this->actingAs($this->manager)->delete(route('projects.destroy', $project))
            ->assertRedirect(route('projects.index'))
            ->assertSessionHas('success', 'The project has been deleted.');

        $this->assertSoftDeleted($project);
    }

    public function test_when_a_project_has_been_deleted_the_status_has_been_changed_to_closed()
    {
        $project = Project::factory()->create();

        $this->actingAs($this->manager)->delete(route('projects.destroy', $project))
            ->assertRedirect(route('projects.index'))
            ->assertSessionHas('success', 'The project has been deleted.');

        $this->assertEquals($project->fresh()->status, 'closed');
    }

    public function test_when_a_project_has_been_deleted_the_manager_is_unassigned()
    {
        $project = Project::factory()->create(['manager_id' => $this->manager]);

        $this->actingAs($this->manager)->delete(route('projects.destroy', $project))
            ->assertRedirect(route('projects.index'))
            ->assertSessionHas('success', 'The project has been deleted.');

        $this->assertEmpty($project->fresh()->manager_id);
    }

    public function test_only_a_user_with_the_restore_project_permission_can_visit_the_trashed_project_overview()
    {
        $project = Project::factory()->create();

        $this->actingAs($this->manager)->get(route('projects.trashed'))
            ->assertForbidden();

        $this->actingAs($this->admin)->get(route('projects.trashed'))
            ->assertOk()
            ->assertSeeText('No trashed projects yet.');

        $deletedProject = Project::factory()->trashed()->create();

        $this->actingAs($this->admin)->get(route('projects.trashed'))
            ->assertOk()
            ->assertSeeText(Str::limit($deletedProject->title, 35))
            ->assertDontSeeText($project->title);
    }

    public function test_a_user_without_the_restore_project_permission_cannot_restore_a_deleted_project()
    {
        $project = project::factory()->trashed()->create();

        $this->actingAs($this->manager)->patch(route('projects.restore', $project))
            ->assertForbidden();

        $this->assertSoftDeleted($project);
    }

    public function test_a_user_with_the_restore_project_permission_can_restore_a_deleted_project()
    {
        $project = project::factory()->trashed()->create();

        $this->actingAs($this->admin)->patch(route('projects.restore', $project))
            ->assertRedirect(route('projects.trashed'))
            ->assertSessionHas('success', 'The project '.$project->title.' has been restored.');

        $this->assertNotSoftDeleted($project);
    }

    public function test_when_a_project_has_been_restored_the_status_has_been_changed_to_restored()
    {
        $project = project::factory()->trashed()->create();

        $this->actingAs($this->admin)->patch(route('projects.restore', $project))
            ->assertRedirect(route('projects.trashed'));

        $this->assertEquals($project->fresh()->status, 'restored');
    }

    public function test_a_user_can_filter_projects_on_their_status()
    {
        $openProject = Project::factory()->create(['status' => 'open']);
        $pendingProject = Project::factory()->create(['status' => 'pending']);
        $closedProject = Project::factory()->create(['status' => 'closed']);
        $completedProject = Project::factory()->create(['status' => 'completed']);

        $this->actingAs($this->manager)->get(route('projects.index'))
            ->assertOk()
            ->assertSeeText([
                Str::limit($openProject->title, 35),
                Str::limit($pendingProject->title, 35),
                Str::limit($closedProject->title, 35),
                Str::limit($completedProject->title, 35),
            ]);

        $this->actingAs($this->manager)->get(route('projects.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSeeText(Str::limit($pendingProject->title, 35))
            ->assertDontSeeText([
                Str::limit($openProject->title, 35),
                Str::limit($closedProject->title, 35),
                Str::limit($completedProject->title, 35),
            ]);
    }
}
