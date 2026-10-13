<?php

namespace App\Providers;

use App\Support\CurrentCompany;
use App\Support\Navigation;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(CurrentCompany::class);
        $this->app->scoped(Navigation::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.app', function ($view) {
            $view->with([
                'navigation' => app(Navigation::class),
                'currentCompany' => app(CurrentCompany::class)->get(),
            ]);
        });
    }
}
