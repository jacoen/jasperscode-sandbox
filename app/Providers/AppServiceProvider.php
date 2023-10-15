<?php

namespace App\Providers;

use App\Services\NewsletterService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use MailchimpMarketing\ApiClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        app()->bind(NewsletterService::class, function () {
            $client = new ApiClient();

            $client->setConfig([
                'apiKey' => config('services.mailchimp.key'),
                'server' => 'us6',
            ]);

            return new NewsletterService($client);

            if ($this->app->environment('local')) {
                $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
                $this->app->register(TelescopeServiceProvider::class);
            }
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrap();

        Model::preventLazyLoading(! $this->app->isProduction());
    }
}
