<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;

class PinnedProjectDestructionException extends Exception
{
    public function __construct($message)
    {
        parent::__construct($message);
    }

    public function render(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Unable to delete project.',
                'message' => $this->getMessage(),
            ], 403);
        }

        return redirect()->route('projects.index')
            ->withErrors([
                'error' => $this->getMessage(),
            ]);
    }
}
