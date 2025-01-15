<?php
namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Определить другие необходимые пути для вашего приложения.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        // Регистрация middleware
        Route::aliasMiddleware('role', \App\Http\Middleware\RoleMiddleware::class);
    }
}
