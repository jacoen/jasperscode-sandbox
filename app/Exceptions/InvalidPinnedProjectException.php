<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InvalidPinnedProjectException extends Exception
{
    protected $project;

    public function __construct($message, $project)
    {
        parent::__construct($message);
        $this->project = $project;
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Invalid pinned project',
                'message' => $this->getMessage(),
                'project' => $this->project,
            ], 422);
        }

        return redirect()->route('projects.edit', $this->project)
            ->withErrors([
                'error' => $this->getMessage(),
            ])->withInput();
    }
}
