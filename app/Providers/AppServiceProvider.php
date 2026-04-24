<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Gate::define('manage-blog', function ($user) {
            return $user->hasRole('admin') || $user->hasRole('editor');
        });

        \Illuminate\Support\Facades\Gate::define('create-blog', function ($user) {
            return $user->hasRole('admin') || $user->hasRole('editor') || $user->hasRole('author');
        });
    }
}
