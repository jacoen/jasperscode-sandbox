<?php

namespace App\Http\Controllers;

use App\Models\Project;

class ExpiredProjectController extends Controller
{

    public function __construct()
    {
        $this->middleware(['permission:read expired projects']);
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke($recent = null)
    {
        $projects = Project::with('manager')->whereIn('status', ['open', 'pending'])->whereBetween('due_date', [ now()->subWeek(), now()])->get();

        $projects = Project::with('manager')
            ->when($recent == '1 week', function ($query) {
                $query->whereBetween('due_date', now()->subWeek(), now());
            })
            ->where('due_date', '<', now())
            ->where('status', 'expired')
            ->orderBy('due_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        return view('projects.expired', compact('projects'));
    }
}
