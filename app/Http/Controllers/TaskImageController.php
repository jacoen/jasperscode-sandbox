<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class TaskImageController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Task $task, Media $image): RedirectResponse
    {
        abort_if(! auth()->user()->can('update task'), 403);

        if ($task->id !== $image->model_id) {
            return redirect()->route('tasks.show', $task)
                ->withErrors(['error' => 'Cannot remove this image.']);
        }

        $image->delete();

        return redirect()->route('tasks.show', $task)
            ->with('success', 'The attachment has been removed.');
    }
}
