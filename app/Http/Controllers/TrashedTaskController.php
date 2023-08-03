<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrashedTaskController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(): View
    {
        $tasks = Task::onlyTrashed()
            ->with('project')
            ->latest('deleted_at')
            ->orderBy('id', 'desc')
            ->paginate();

        return view('tasks.trashed', compact('tasks'));
    }
}
