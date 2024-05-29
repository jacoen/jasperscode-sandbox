<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;

class CreateTaskException extends Exception
{
    protected $project;

    public function __construct($message, $project)
    {
        parent::__construct($message);

        $this->project = $project;
    }

    public function render(Request $request)
    {
        return redirect()->route('projects.show', $this->project)
            ->withErrors([
                'error' => $this->getMessage(),
            ]);
    }
}
