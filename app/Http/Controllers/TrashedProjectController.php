<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\View\View;

class TrashedProjectController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(): View
    {
        $projects = Project::onlyTrashed()
            ->with('manager')
            ->latest('deleted_at')
            ->orderBy('id', 'desc')
            ->paginate();

        return view('projects.trashed', compact('projects'));
    }
}
