<?php

namespace Tests\Feature\Controllers;

use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TaskImageControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $task;

    public function setUp(): void
    {
        parent::setUp();

        Storage::fake('media');

        $this->task = Task::factory()->create();
        $file = UploadedFile::fake()->image('test.jpg');

        $this->task->addMedia($file)->toMediaCollection('media');
    }

    public function test_a_guest_cannot_delete_an_attachment_from_a_task()
    {
        $image = $this->task->getFirstMedia('media');

        $this->delete(route('task-image.delete', [
            'task' => $this->task,
            'image' => $image,
        ]))->assertRedirect(route('login'));

        Storage::disk('media')->assertExists('/'.$this->task->id.'/test.jpg');

        $this->assertCount(1, $this->task->getMedia('media'));
    }

    public function test_a_user_without_the_edit_task_permission_cannot_delete_an_attachment_from_a_task()
    {
        $image = $this->task->getFirstMedia('media');

        $this->actingAs($this->user)->delete(route('task-image.delete', [
            'task' => $this->task,
            'image' => $image,
        ]))->assertForbidden();

        Storage::disk('media')->assertExists('/'.$this->task->id.'/test.jpg');

        $this->assertCount(1, $this->task->getMedia('media'));
    }

    public function test_a_user_with_the_edit_task_permission_cannot_delete_an_image_from_another_task()
    {
        $task = Task::factory()->create(['user_id' => $this->employee->id]);
        $extraFile = UploadedFile::fake()->image('anotherFile.jpg');

        $task->addMedia($extraFile)->toMediaCollection('media');

        $image = $task->getFirstMedia('media');

        $response = $this->actingAs($this->employee)->delete(route('task-image.delete', [
            'task' => $this->task,
            'image' => $image,
        ]));

        $response->assertSessionHasErrors(['error' => 'Cannot remove this image.']);

        $this->assertCount(1, $task->getMedia('media'));
    }

    public function test_a_user_with_the_edit_task_permission_can_delete_an_image_from_a_task()
    {
        $image = $this->task->getFirstMedia('media');

        $this->actingAs($this->employee)->delete(route('task-image.delete',
            [
                'task' => $this->task,
                'image' => $image,
            ])
        )->assertRedirect(route('tasks.show', $this->task))
            ->assertSessionHas('success', 'The attachment has been removed.');

    }
}
