<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Notifications\ProjectAlertNotification;
use App\Notifications\ProjectWarningNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProjectDueDateCommandTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    public function test_there_will_be_no_due_date_notification_when_the_due_date_of_a_project_is_more_than_a_month_away()
    {
        $project = Project::factory()->create(['manager_id' => $this->manager->id, 'status' => 'open', 'due_date' => now()->addDays(35)->toDateString()]);
        $projectWithoutManager = Project::factory()->create(['status' => 'open', 'due_date' => now()->addDays(35)->toDateString()]);

        $this->artisan('project:due-date-check')->assertSuccessful();

        Notification::assertNothingSent();
        Notification::assertCount(0);
    }

    public function test_a_project_manager_receives_a_notification_on_weekdays_when_the_end_date_of_its_active_project_is_less_than_a_week_away()
    {
        Carbon::setTestNow(Carbon::parse('next tuesday'));

        $newManager = User::factory()->create(['name' => 'John Manager', 'email' => 'john.manager@example.com']);
        $project = Project::factory()->create(['manager_id' => $this->manager->id, 'status' => 'open', 'due_date' => now()->addDays(5)->toDateString()]);
        $closedProject = Project::factory()->create(['manager_id' => $newManager->id, 'status' => 'closed', 'due_date' => now()->addDays(5)->toDateString()]);
        
        $this->artisan('project:due-date-check')->assertSuccessful();

        Notification::assertSentTo($this->manager, ProjectAlertNotification::class);
        Notification::assertNotSentTo($newManager, ProjectAlertNotification::class);
        Notification::assertCount(1);
    }

    public function test_an_admin_receives_a_notification_on_weekdays_when_the_due_date_of_an_active_project_without_a_project_manager_is_less_than_a_week_away()
    {
        Carbon::setTestNow(Carbon::parse('next tuesday'));

        $project = Project::factory()->create(['manager_id' => null, 'due_date' => now()->addDays(5)->toDateString()]);
        $closedProject = Project::factory()->create(['status' => 'closed', 'due_date' => now()->addDays(5)->toDateString()]);

        $this->artisan('project:due-date-check')->assertSuccessful();

        Notification::assertSentTo(
            $this->admin,
            function (ProjectAlertNotification $notification, $channels) use ($project) {
                return $notification->getProject()->id === $project->id;
            }
        );

        Notification::assertNotSentTo(
            $this->admin,
            function (ProjectAlertNotification $notification, $channels) use ($closedProject) {
                return $notification->getProject()->id === $closedProject->id;
            }
        );
    }

    public function test_no_notification_will_be_send_during_the_weekend_when_the_due_date_of_an_active_project_is_less_than_a_week_away()
    {
        Carbon::setTestNow(Carbon::parse('next saturday'));

        Project::factory()->create(['manager_id' => $this->manager, 'due_date' => now()->addDays(6)->toDateString()]);
        Project::factory()->create(['manager_id' => null, 'due_date' => now()->addDays(6)->toDateString()]);

        $this->artisan('project:due-date-check')->assertSuccessful();

        Notification::assertNotSentTo($this->manager, ProjectAlertNotification::class);
        Notification::assertNotSentTo($this->admin, ProjectAlertNotification::class);
    }

    public function test_a_project_manager_receives_a_notification_every_monday_when_the_end_date_of_its_active_project_is_less_than_a_month_away()
    {
        Carbon::setTestNow(Carbon::parse('next monday'));
        $project = Project::factory()->create(['manager_id' => $this->manager->id, 'due_date' => now()->addDays(25)->toDateString()]);
        $closedProject = Project::factory()->create(['manager_id' => $this->manager->id, 'status' => 'closded', 'due_date' => now()->addDays(25)->toDateString()]);

        $this->artisan('project:due-date-check')->assertSuccessful();

        Notification::assertSentTo(
            $this->manager,
            function (ProjectWarningNotification $notification, $channels) use ($project) {
                return $notification->getProject()->id === $project->id;
            }
        );
        
        Notification::assertNotSentTo(
            $this->manager,
            function (ProjectWarningNotification $notification, $channels) use ($closedProject) {
                return $notification->getProject()->id === $closedProject->id;
            }
        );

        Carbon::setTestNow();
    }

    public function test_an_admin_receives_a_notification_once_a_week_that_the_end_date_of_an_active_project_without_a_project_manager_is_less_than_a_month_away()
    {
        Carbon::setTestNow(Carbon::parse('next monday'));
        
        $project = Project::factory()->create(['manager_id' => null, 'due_date' => now()->addDays(25)->toDateString()]);
        $closedProject = Project::factory()->create(['manager_id' => null, 'status' => 'closed', 'due_date' => now()->addDay(25)->toDateString()]);

        $this->artisan('project:due-date-check')->assertSuccessful();

        Notification::assertSentTo(
            $this->admin,
            function (ProjectWarningNotification $notification, $channels) use ($project) {
                return $notification->getProject()->id === $project->id;
            }
        );

        Notification::assertNotSentTo(
            $this->admin,
            function (ProjectWarningNotification $notification, $channels) use ($closedProject) {
                return $notification->getProject()->id === $closedProject->id;
            }
        );
    }

    public function test_a_project_manager_or_admin_will_not_receive_a_notification_of_a_project_with_a_due_date_that_is_less_than_a_month_away_on_other_days_than_monday()
    {
        Carbon::setTestNow(Carbon::parse('next thursday'));

        $projectWithoutManager = Project::factory()->create(['manager_id' => null, 'due_date' => now()->addDays(25)->toDateString()]);
        $projectWithManager = Project::factory()->create(['manager_id' => $this->manager->id, 'due_date' => now()->addDays(5)->toDateString()]);

        $this->artisan('project:due-date-check')->assertSuccessful();

        Notification::assertNotSentTo($this->admin, ProjectWarningNotification::class);
        Notification::assertNotSentTo($this->manager, ProjectWarningNotification::class);
    }
    // test geen notificatie in weekend bij alert

    // test geen notificatie op andere dag dan maandag bij warning

    // test commando project met aflopende due date in een maand, zonder manager
}
