<?php

namespace App\Http\Controllers;

use App\Services\ProjectService;

class ExpiredProjectController extends Controller
{

    public function __construct()
    {
        $this->middleware(['permission:read expired projects']);
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(ProjectService $projectService)
    {
        $projects = $projectService->listExpiredProjects(request()->input('yearWeek'));

        return view('projects.expired', compact('projects'));
    }
}
