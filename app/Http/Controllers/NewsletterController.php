<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\NewsletterService;
use Illuminate\Validation\ValidationException;

class NewsletterController extends Controller
{
    public function create()
    {
        return view('newsletters.subscribe');
    }

    public function store(NewsletterService $newsletter, Request $request)
    {
        $request->validate(['email' => 'required|email']);

        try {
            $newsletter->subscribe(request('email'));
        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'email' => 'This email could not be add to our newsletter list.',
            ]);
        }

        return redirect()->route('welcome')
            ->with('success', 'You are now signed up for our newsletter.');
    }
}
