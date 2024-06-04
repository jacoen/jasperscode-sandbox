<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;

class UpdateTaskException extends Exception
{
    protected $task;

    public function __construct($message, $task)
    {
        parent::__construct($message);

        $this->task = $task;
    }

    public function render(Request $request)
    {
        return redirect()->route('tasks.show', $this->task)
            ->withErrors([
                'error' => $this->getMessage(),
            ]);
    }
}
