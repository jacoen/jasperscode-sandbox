<?php

namespace Tests\Feature\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\ProjectAssignedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProjectControllerTest extends TestCase
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

    public function test_a_user_can_filter_projects_on_their_status()
    {
        $openProject = Project::factory()->create(['status' => 'open']);
        $pendingProject = Project::factory()->create(['status' => 'pending']);
        $closedProject = Project::factory()->create(['status' => 'closed']);
        $completedProject = Project::factory()->create(['status' => 'completed']);

        $this->actingAs($this->employee)->get(route('projects.index'))
            ->assertOk()
            ->assertSeeText([
                Str::limit($openProject->title, 35),
                Str::limit($pendingProject->title, 35),
                Str::limit($closedProject->title, 35),
                Str::limit($completedProject->title, 35),
            ]);

        $this->actingAs($this->employee)->get(route('projects.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSeeText(Str::limit($pendingProject->title, 35))
            ->assertDontSeeText([
                Str::limit($openProject->title, 35),
                Str::limit($closedProject->title, 35),
                Str::limit($completedProject->title, 35),
            ]);
    }

    public function test_a_user_can_search_project_by_their_title()
    {
        $project1 = Project::factory()->create(['manager_id' => $this->manager->id, 'title' => 'This is the first project']);
        $project2 = Project::factory()->create(['manager_id' => $this->manager->id, 'title' => 'This is the second project']);
        $project3 = Project::factory()->create(['manager_id' => $this->manager->id, 'title' => 'This is the third project']);

        $this->actingAs($this->employee)->get(route('projects.index'))
            ->assertOk()
            ->assertSeeText([
                Str::limit($project1->title, 35),
                Str::limit($project2->title, 35),
                Str::limit($project3->title, 35),
            ]);

        $this->actingAs($this->employee)->get(route('projects.index', ['search' => 'second']))
            ->assertOk()
            ->assertSeeText([Str::limit($project2->title, 35)])
            ->assertDontSeeText([
                Str::limit($project1->title, 35),
                Str::limit($project3->title, 35),
            ]);
    }

    public function test_a_user_without_the_create_project_permission_cannot_visit_the_project_create_page()
    {
        $this->get(route('projects.create'))
            ->assertRedirect(route('login'));

        $this->actingAs($this->user)->get(route('projects.create'))
            ->assertForbidden();
    }

    public function test_a_user_without_the_create_permission_cannot_create_a_new_project()
    {
        $data = [
            'title' => 'Some new title',
            'description' => 'Here is some description for the test',
            'due_date' => now()->addMonths(3),
            'manager_id' => $this->manager->id,
        ];

        $this->actingAs($this->employee)->post(route('users.store'), $data)
            ->assertForbidden();

        $this->assertDatabaseMissing('projects', $data);
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

        $this->assertDatabaseEmpty('projects');
    }

    public function test_the_due_date_must_be_after_the_current_date_when_creating_a_project()
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

    public function test_a_manager_receives_a_message_when_a_new_project_has_been_assigned_to_them()
    {
        Notification::fake();

        $data = [
            'title' => 'Some new title',
            'description' => 'Here is some description for the test',
            'due_date' => now()->addMonths(3),
            'manager_id' => $this->manager->id,
        ];

        $this->actingAs($this->manager)->post(route('projects.store'), $data)
            ->assertRedirect(route('projects.index'))
            ->assertSessionHas('success');

        Notification::assertSentTo($this->manager, ProjectAssignedNotification::class);
    }

    public function test_a_user_without_the_read_project_permission_cannot_visit_the_project_detail_page()
    {
        $project = Project::factory()->create(['manager_id' => null]);

        $this->get(route('projects.show', $project))
            ->assertRedirect(route('login'));

        $this->actingAs($this->user)->get(route('projects.show', $project))
            ->assertForbidden();
    }

    public function test_a_user_with_the_read_project_permission_can_visit_the_project_detail_page()
    {
        $project = Project::factory()->create(['manager_id' => null]);

        $this->actingAs($this->employee)->get(route('projects.show', $project))
            ->assertOk()
            ->assertSeeText([
                $project->title,
                $project->description,
                $project->due_date->format('d M Y'),
                $project->status,
            ]);
    }

    public function test_an_alert_is_shown_when_an_active_project_is_due_in_a_week_or_less()
    {
        $project = Project::factory()->create(['manager_id' => $this->manager->id, 'status' => 'open', 'due_date' => now()->addDays(6)]);
        $tomorrowProject = Project::factory()->create(['manager_id' => $this->manager->id, 'status' => 'open', 'due_date' => now()->addDay()]);
        $inactiveProject = Project::factory()->create(['manager_id' => $this->manager->id, 'status' => 'closed', 'due_date' => now()->addDay()]);

        $this->actingAs($this->employee)->get(route('projects.show', $project))
            ->assertOk()
            ->assertSeeText('Info This project is due in 6 days.');

        $this->actingAs($this->employee)->get(route('projects.show', $tomorrowProject))
            ->assertOk()
            ->assertSeeText('Info This project is due tomorrow.');

        $this->actingAs($this->employee)->get(route('projects.show', $inactiveProject))
            ->assertOk()
            ->assertDontSeeText('Info This project is due tomorrow.');
    }

    public function test_a_warning_is_shown_when_an_active_project_is_due_in_less_than_a_month()
    {
        $project = Project::factory()->create(['manager_id' => $this->manager->id, 'status' => 'open', 'due_date' => now()->addDays(25)]);
        $inactiveProject = Project::factory()->create(['manager_id' => $this->manager->id, 'status' => 'closed', 'due_date' => now()->addDays(25)]);

        $this->actingAs($this->employee)->get(route('projects.show', $project))
            ->assertOk()
            ->assertSeeText('Info This project is due in 25 days.');

        $this->actingAs($this->employee)->get(route('projects.show', $inactiveProject))
            ->assertOk()
            ->assertDontSeeText('Info This project is due in 25 days.');
    }

    public function test_when_a_project_has_tasks_the_user_can_see_these_tasks_on_the_project_detail_page()
    {
        $project = Project::factory()->create();

        $this->actingAs($this->employee)->get(route('projects.show', $project))
            ->assertOk()
            ->assertSeeText('No tasks in this project yet.');

        $task = Task::factory()->for($project)->create();

        $this->actingAs($this->employee)->get(route('projects.show', $project))
            ->assertOk()
            ->assertSeeText([
                $task->title,
                $task->author->name,
                $task->user->name,
                $task->status,
            ]);
    }

    public function test_a_user_can_filter_the_tasks_related_to_a_project_by_the_status_of_the_task()
    {
        $project = Project::factory()->create();

        $openTask = Task::factory()->for($project)->create(['status' => 'open']);
        $pendingTask = Task::factory()->for($project)->create(['status' => 'pending']);
        $closedTask = Task::factory()->for($project)->create(['status' => 'closed']);
        $completedTask = Task::factory()->for($project)->create(['status' => 'completed']);

        $this->actingAs($this->employee)->get(route('projects.show', $project))
            ->assertOk()
            ->assertSeeText([
                $openTask->title,
                $pendingTask->title,
                $closedTask->title,
                $completedTask->title,
            ]);

        $this->actingAs($this->employee)->get(route('projects.show', ['project' => $project, 'status' => 'pending']))
            ->assertOk()
            ->assertSeeText([
                $pendingTask->title,
                $pendingTask->status,
            ])->assertDontSeeText([
                $openTask->title,
                $closedTask->title,
                $completedTask->title,
            ]);
    }

    public function test_a_user_can_search_all_task_related_to_a_project_by_their_title()
    {
        $project = Project::factory()->create();

        $task1 = Task::factory()->for($project)->create(['title' => 'First task']);
        $task2 = Task::factory()->for($project)->create(['title' => 'This is the second task']);
        $task3 = Task::factory()->for($project)->create(['title' => 'Task number three']);

        $this->actingAs($this->employee)->get(route('projects.show', $project))
            ->assertOk()
            ->assertSeeText([
                $task1->title,
                $task2->title,
                $task3->title,
            ]);

        $this->actingAs($this->employee)->get(route('projects.show', ['project' => $project, 'search' => 'second']))
            ->assertOk()
            ->assertSeeText($task2->title)
            ->assertDontSeeText([
                $task1->title,
                $task3->title,
            ]);
    }

    public function test_a_guest_cannot_reach_the_edit_project_page()
    {
        $project = Project::factory()->create();

        $this->get(route('projects.edit', $project))
            ->assertRedirect(route('login'));
    }

    public function test_a_user_without_the_update_project_permission_cannot_visit_the_edit_project_page_to_edit_a_project()
    {
        $project = Project::factory()->create();

        $data = [
            'title' => $project->title,
            'description' => $project->description,
            'manager_id' => $this->manager->id,
            'status' => 'open',
        ];

        $this->actingAs($this->employee)->get(route('projects.edit', $project))
            ->assertForbidden();

        $this->actingAs($this->employee)->put(route('projects.update', $project), $data)
            ->assertForbidden();

        $this->assertDatabaseMissing('projects', $data);
    }

    public function test_multiple_fields_are_required_when_updating_the_project()
    {
        $project = Project::factory()->create();

        $data = [
            'title' => '',
            'due_date' => '',
            'status' => '',
        ];

        $this->actingAs($this->manager)->put(route('projects.update', $project), $data)
            ->assertSessionHasErrors([
                'title' => 'The title field is required.',
                'due_date' => 'The due date field is required.',
                'status' => 'The status field is required.',
            ]);
    }

    public function test_the_status_field_must_contain_a_valid_status_when_updating_a_project()
    {
        $project = Project::factory()->create();

        $data = [
            'title' => $project->title,
            'description' => $project->description,
            'manager_id' => $this->manager->id,
        ];

        $this->actingAs($this->manager)->put(route('projects.update', $project), array_merge($data, ['status' => 'restored']))
            ->assertSessionHasErrors([
                'status' => 'The selected status is invalid.',
            ]);

        $this->actingAs($this->manager)->put(route('projects.update', $project), array_merge($data, ['status' => 'expired']))
            ->assertSessionHasErrors([
                'status' => 'The selected status is invalid.',
            ]);

        $this->assertNotEquals($project->fresh()->status, 'restored');
        $this->assertDatabaseMissing('projects', array_merge($data, ['id' => $project->id, 'status' => 'restored']));

        $this->assertNotEquals($project->fresh()->status, 'expired');
        $this->assertDatabaseMissing('projects', array_merge($data, ['id' => $project->id, 'status' => 'expired']));
    }

    public function test_a_user_with_the_update_project_permission_can_update_a_project()
    {
        $project = Project::factory()->create();

        $data = [
            'title' => 'A new title',
            'description' => $project->description,
            'manager_id' => $this->manager->id,
            'due_date' => $project->due_date,
            'status' => 'pending',
        ];

        $this->actingAs($this->manager)->get(route('projects.edit', $project))
            ->assertOk();

        $this->actingAs($this->manager)->put(route('projects.update', $project), $data)
            ->assertRedirect(route('projects.show', $project));

        $this->assertDatabaseHas('projects', array_merge(['id' => $project->id], $data));
    }

    public function test_only_a_user_with_the_admin_role_can_pin_a_project()
    {
        $project = Project::factory()->create(['manager_id' => $this->manager->id]);

        $data = [
            'title' => $project->title,
            'manager_id' => $this->manager->id,
            'due_date' => $project->due_date->format('d M Y'),
            'status' => $project->status,
            'is_pinned' => 1,
        ];

        $this->actingAs($this->manager)->put(route('projects.update', $project), $data)
            ->assertSessionHasErrors([
                'error' => 'User is not authorized to pin a project',
            ]);
    }

    public function test_only_one_project_can_be_pinned_at_a_time()
    {
        $this->actingAsVerifiedTwoFactor($this->admin);

        $firstProject = Project::factory()->create(['manager_id' => $this->manager->id]);
        $secondProject = Project::factory()->create(['manager_id' => $this->manager->id]);

        $data = [
            'title' => $firstProject->title,
            'description' => $firstProject->description,
            'status' => $firstProject->status,
            'manager_id' => $firstProject->manager_id,
            'due_date' => $firstProject->due_date,
            'is_pinned' => 1,
        ];

        $secondData = [
            'title' => $secondProject->title,
            'description' => $secondProject->description,
            'status' => 'open',
            'due_date' => $secondProject->due_date,
            'is_pinned' => 1,
        ];

        $this->put(route('projects.update', $secondProject), $secondData)
            ->assertRedirect(route('projects.show', $secondProject));

        $this->put(route('projects.update', $firstProject), $data)
            ->assertSessionHasErrors([
                'error' => 'There is a pinned project already. If you want to pin this project you will have to unpin the other project.',
            ]);

        $this->assertDatabaseHas('projects', [
            'id' => $secondProject->id,
            'is_pinned' => 1,
        ]);

        $this->assertDatabaseHas('projects', [
            'id' => $firstProject->id,
            'is_pinned' => 0,
        ]);
    }

    public function test_when_an_existing_project_gets_assigned_to_a_manager_this_manager_will_receive_a_notification()
    {
        Notification::fake();

        $manager = User::factory()->create()->assignRole('Manager');
        $project = Project::factory()->create(['manager_id' => $manager->id]);

        $data = [
            'title' => $project->title,
            'description' => $project->description,
            'manager_id' => $this->manager->id,
            'due_date' => $project->due_date->format('d M Y'),
            'status' => 'open',
        ];

        $this->actingAs($this->manager)->put(route('projects.update', $project), $data)
            ->assertRedirect(route('projects.show', $project));

        Notification::assertSentTo($this->manager, ProjectAssignedNotification::class);
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

    public function test_a_guest_cannot_visit_the_trashed_project_page()
    {
        $this->get(route('projects.trashed'))->assertRedirect(route('login'));
    }

    public function test_a_user_without_the_restore_project_permission_cannot_visit_the_trashed_project_page()
    {
        $this->actingAs($this->employee)->get(route('projects.trashed'))
            ->assertForbidden();
    }

    public function test_a_user_with_the_restore_project_permission_can_visit_the_trashed_project_page()
    {
        $this->actingAsVerifiedTwoFactor($this->admin);

        $this->get(route('projects.trashed'))
            ->assertOk()
            ->assertSeeText('No trashed projects yet.');

        $project1 = Project::factory()->trashed()->create();
        $project2 = Project::factory()->trashed()->create();

        $this->get(route('projects.trashed'))
            ->assertOk()
            ->assertSeeText([
                Str::limit($project1->title, 35),
                Str::limit($project2->title, 35),
            ]);
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
        $this->actingAsVerifiedTwoFactor($this->admin);

        $project = project::factory()->trashed()->create();

        $this->patch(route('projects.restore', $project))
            ->assertRedirect(route('projects.trashed'))
            ->assertSessionHas('success', 'The project '.$project->title.' has been restored.');

        $this->assertNotSoftDeleted($project);
    }

    public function test_a_user_with_the_force_delete_permission_can_permanently_delete_a_project()
    {
        $this->actingAsVerifiedTwoFactor($this->admin);

        $project = Project::factory()->trashed()->create();

        $this->assertSoftDeleted($project);

        $this->patch(route('projects.force-delete', $project))
            ->assertRedirect(route('projects.trashed'))
            ->assertSessionHas('success', 'The project has been permanently deleted.');

        $this->assertNull($project->fresh());
    }

    protected function actingAsVerifiedTwoFactor($user)
    {
        $this->actingAs($user);

        $this->post(route('verify.store'), [
            'two_factor_code' => $user->two_factor_code,
        ]);
    }
}
