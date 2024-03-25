<?php

namespace Tests\Feature\Commands;

use App\Models\Project;
use App\Notifications\ProjectExpirationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ExpiredProjectCommandTest extends TestCase
{
   use RefreshDatabase;

   public function setUp():void
   {
     parent::setUp();

     Notification::fake();
   }

   public function test_it_does_not_change_the_status_of_a_project_to_expired_if_the_due_date_has_not_yet_expired()
   {
     $project = Project::factory()->create(['due_date' => now()->addDays(2)]);

     $this->artisan('project:check-expiration')->assertSuccessful();

     $this->assertEquals($project->fresh()->status, 'open');
   }

   public function test_it_does_not_change_the_status_of_a_closed_project_with_an_expired_due_date()
   {
     $project = Project::factory()->expiredWithStatus()->create(['status' => 'closed']);

     $this->artisan('project:check-expiration')->assertSuccessful();

     $this->assertEquals($project->fresh()->status, 'closed');
   }

   public function test_it_does_not_change_the_status_of_a_completed_project_with_an_expired_due_date()
   {
     $project = Project::factory()->expiredWithStatus()->create(['status' => 'completed']);

     $this->artisan('project:check-expiration')->assertSuccessful();

     $this->assertEquals($project->fresh()->status, 'completed');
   }

   public function test_it_does_not_change_the_status_of_a_trashed_project_with_an_expired_due_date()
   {
     $project = Project::factory()->trashed()->create(['due_date' => now()->subMinutes(3)]);

     $this->artisan('project:check-expiration')->assertSuccessful();

     $this->assertEquals($project->fresh()->status, 'closed');
}

   public function test_it_does_change_the_status_of_an_open_project_when_the_due_date_has_expired()
   {
     $project = Project::factory()->expiredWithStatus()->create(['status' => 'open']);

     $this->artisan('project:check-expiration')->assertSuccessful();

     $this->assertEquals($project->fresh()->status, 'expired');
   }

   public function test_it_does_change_the_status_of_a_pending_project_when_the_due_date_has_expired()
   {
     $project = Project::factory()->expiredWithStatus()->create(['status' => 'pending']);

     $this->artisan('project:check-expiration')->assertSuccessful();

     $this->assertEquals($project->fresh()->status, 'expired');
   }

   public function test_it_does_change_the_status_of_a_restored_project_with_an_expired_due_date()
   {
     $project = Project::factory()->expiredWithStatus()->create(['status' => 'restored']);

     $this->artisan('project:check-expiration')->assertSuccessful();

     $this->assertEquals($project->fresh()->status, 'expired');
   }

   public function test_it_does_change_the_is_pinned_status_of_a_project_when_the_due_date_of_a_pinned_project_expires()
   {
     $project = Project::factory()->expiredWithStatus()->create(['status' => 'pending', 'is_pinned' => true]);

     $this->artisan('project:check-expiration')->assertSuccessful();

     $this->assertFalse($project->fresh()->is_pinned);
   }

   public function test_it_does_not_send_a_notification_when_an_expired_project_is_not_assigned_to_a_manager()
   {
     $project = Project::factory()->expiredWithStatus()->create(['status' => 'open', 'manager_id' => null]);

     $this->artisan('project:check-expiration')->assertSuccessful();

     Notification::assertNothingSent();
   }

   public function test_it_does_send_a_notification_when_an_expired_project_is_assign_to_a_manager()
   {
     $project = Project::factory()->expiredWithStatus()->create(['status' => 'open', 'manager_id' => $this->manager->id]);

     $this->artisan('project:check-expiration')->assertSuccessful();

     Notification::assertSentTo($this->manager, ProjectExpirationNotification::class);
   }
}
