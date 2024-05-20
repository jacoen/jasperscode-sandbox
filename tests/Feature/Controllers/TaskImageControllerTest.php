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

    protected $file;

    public function setUp(): void
    {
        parent::setUp();

        Storage::fake('media');

        $this->task = Task::factory()->create();
        $this->file = UploadedFile::fake()->image('test.jpg');

        $this->task->addMedia($this->file)->toMediaCollection('attachments');
    }

    public function test_a_guest_cannot_delete_an_attachment_from_a_task()
    {
        $image = $this->task->getFirstMedia('attachments');

        $this->delete(route('task-image.delete', [
            'task' => $this->task,
            'image' => $image,
        ]))->assertRedirect(route('login'));

        Storage::disk('media')->assertExists('/'.$image->id.'/test.jpg');

        $this->assertCount(1, $this->task->getMedia('attachments'));
    }

    public function test_a_user_without_the_edit_task_permission_cannot_delete_an_attachment_from_a_task()
    {
        $image = $this->task->getFirstMedia('attachments');

        $this->actingAs($this->user)->delete(route('task-image.delete', [
            'task' => $this->task,
            'image' => $image,
        ]))->assertForbidden();

        Storage::disk('media')->assertExists('/'.$image->id.'/test.jpg');

        $this->assertCount(1, $this->task->getMedia('attachments'));
    }

    public function test_a_user_with_the_edit_task_permission_can_delete_an_image_from_a_task()
    {
        $image = $this->task->getFirstMedia('attachments');

        $this->actingAs($this->employee)->delete(route('task-image.delete',
            [
                'task' => $this->task,
                'image' => $image,
            ])
        )->assertRedirect(route('tasks.show', $this->task))
            ->assertSessionHas('success', 'The attachment has been removed.');

        Storage::disk('media')->assertMissing('/'.$image->id.'/'.$this->file->getClientOriginalName());
        $this->assertFileDoesNotExist($image);
    }

    public function test_a_user_with_the_edit_task_permission_cannot_delete_an_image_from_another_task()
    {
        $task = Task::factory()->create(['user_id' => $this->employee->id]);
        $extraFile = UploadedFile::fake()->image('anotherFile.jpg');

        $task->addMedia($extraFile)->toMediaCollection('attachments');

        $image = $task->getFirstMedia('attachments');

        $response = $this->actingAs($this->employee)->delete(route('task-image.delete', [
            'task' => $this->task,
            'image' => $image,
        ]));

        $response->assertSessionHasErrors(['error' => 'Cannot remove this image.']);

        $this->assertCount(1, $task->getMedia('attachments'));
    }
}
