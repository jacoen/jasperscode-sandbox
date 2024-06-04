<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class InvalidPinnedProjectException extends Exception
{
    protected $project;

    public function __construct($message, $project)
    {
        parent::__construct($message);
        $this->project = $project;
    }

    public function render(Request $request): RedirectResponse|Response
    {
        return redirect()->route('projects.edit', $this->project)
            ->withErrors([
                'error' => $this->getMessage(),
            ])->withInput();
    }
}
