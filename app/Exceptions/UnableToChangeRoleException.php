<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UnableToChangeRoleException extends Exception
{
    protected $user;

    public function __construct($message, $user)
    {
        parent::__construct($message);

        $this->user = $user;
    }

    public function render(Request $request): RedirectResponse|Response
    {
        return redirect()->route('users.edit', $this->user)
            ->withErrors([
                'error' => $this->getMessage(),
            ])->withInput();
    }
}
