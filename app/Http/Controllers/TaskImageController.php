<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class TaskImageController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Task $task, Media $image)
    {
        // if ($task->id == $image->id) {
        //     return redirect()->route('tasks.show', $task)
        //         ->withErrors(['errors' => 'Cannot remove this image.']); 
        // }

        $image->delete();

        return redirect()->route('tasks.show', $task)
            ->with('success', 'The attachment has been removed.');
    }
}
