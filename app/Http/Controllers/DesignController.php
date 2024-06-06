<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DesignController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(string $design)
    {
        abort_if(! view()->exists('design.'.$design), 404);

        return view('design.'.$design);
    }
}
