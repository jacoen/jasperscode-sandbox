<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;

class UnauthorizedPinException extends Exception
{
    protected $project;

    public function __construct($message, $project)
    {
        parent::__construct($message);
        $this->project = $project;
    }

    public function render(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Not authorized',
                'message' => $this->getMessage(),
            ], 403);
        }

        return redirect()->route('projects.edit', $this->project)
            ->withErrors([
                'error' => $this->getMessage(),
            ])->withInput();
    }
}
