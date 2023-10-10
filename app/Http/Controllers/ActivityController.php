<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\Activitylog\Models\Activity;

class ActivityController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        Gate::allows('read-activity');

        $activities = Activity::with('causer')
            ->latest()
            ->orderByDesc('id')
            ->paginate(10);

        return view('activities.index', compact('activities'));
    }
}
