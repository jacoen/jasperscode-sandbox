<?php

namespace Tests\Feature\Commands;

use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ExpiredProjectReportCommandTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    public function test_it_displays_a_message_if_there_were_no_expired_active_projects_last_week()
    {
        Project::factory()->expiredWithStatus(14)->create();

        $this->artisan('project:expiration-report')
            ->expectsOutput('There was no active project with an expired due_date last week. Goob job!')
            ->assertSuccessful();
    }

    public function test_no_notification_will_be_sent_if_there_are_no_expired_active_project_last_week()
    {
        Project::factory()->expiredWithStatus(14)->create();

        $this->artisan('project:expiration-report')
            ->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_the_message_will_have_a_code_if_there_were_active_project_that_have_expired_last_week()
    {
        $yearWeek = now()->year.'-'.now()->subWeek()->weekOfYear;
        Project::factory()->expiredWithStatus(7)->create();
        
        $this->artisan('project:expiration-report')
            ->expectsOutputToContain($yearWeek)
            ->assertSuccessful();
    }
}
