<?php

namespace App\Providers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;

class RequestMarcroServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Request::macro('validatedExcept', function($except = []) {
            return Arr::except($this->validated(), $except);
        });

        Request::macro('validatedOnly', function($only = []) {
            return Arr::only($this->validated(), $only);
        });
    }
}
