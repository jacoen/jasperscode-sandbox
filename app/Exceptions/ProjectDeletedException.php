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
        return redirect()->route('tasks.trashed')
            ->withErrors([
                'error' => $this->getMessage(),
            ]);
    }
}
