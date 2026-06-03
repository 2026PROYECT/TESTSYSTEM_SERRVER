<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
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
    public function boot()
{
    ResetPassword::createUrlUsing(function ($user, string $token) {
        // Usamos env('FRONTEND_URL') para que en local mande a localhost:3000 
        // y en el servidor mande a escidiomasejto.site
        return config('app.frontend_url') . '/reset-password?token=' . $token . '&email=' . $user->email;
    });
}
}
