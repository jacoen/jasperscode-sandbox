<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;

class ProjectDeletedException extends Exception
{
    public function __construct($message)
    {
        parent::__construct($message);
    }

    public function render(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
            ], 422);
        }

        return redirect()->route('tasks.trashed')
            ->withErrors([
                'error' => $this->getMessage(),
            ]);
    }
}
