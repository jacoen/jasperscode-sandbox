<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Carbon\Carbon;

class ExpiredProjectController extends Controller
{

    public function __construct()
    {
        $this->middleware(['permission:read expired projects']);
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke()
    {
        $yearWeek = request()->input('yearWeek');

        $projects = Project::with('manager')
            ->when($yearWeek, function($query) use ($yearWeek) {
                [$startWeek, $endweek] = $this->spliceYearWeek($yearWeek);
                $query->whereBetween('due_date', [
                    $startWeek,
                    $endweek
                ]);
            })
            ->where('due_date', '<', now()->startOfDay())
            ->orderByDesc('due_date')
            ->orderByDesc('id')
            ->paginate(15);

        return view('projects.expired', compact('projects'));
    }

    protected function spliceYearWeek($yearWeek)
    {
        [$year, $week] = explode('-', $yearWeek);

        $startWeek = Carbon::now()->setISODate($year, $week)->startOfWeek();
        $endWeek = Carbon::now()->setISODate($year, $week)->endOfWeek();

        return [$startWeek, $endWeek];
    }
}
