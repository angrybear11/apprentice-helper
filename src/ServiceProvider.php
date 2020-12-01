<?php

namespace Voronoi\Apprentice;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Illuminate\Support\Facades\Gate;
use Voronoi\Apprentice\Http\Controllers\SSECommandController;
use Voronoi\Apprentice\Http\Controllers\InvitationController;
use Voronoi\Apprentice\Http\Middleware\SignatureAuthentication;
use Voronoi\Apprentice\Console\Commands\Setup as SetupCommand;
use Voronoi\Apprentice\Console\Commands\Info as InfoCommand;
use Voronoi\Apprentice\Artisan\CommandHelper;
use Route;
use Voronoi\Apprentice\Session;

class ServiceProvider extends BaseServiceProvider
{
    public function boot()
    {
        $this->registerMigrations();
        $this->registerRoutes();
        $this->registerCommands();
        $this->publishes([
            __DIR__.'/config/apprentice.php' => config_path('apprentice.php'),
        ], 'apprentice');
        $this->registerSession();
    }

    public function register()
    {
        $this->registerApprenticeCommandHelper();
        $this->mergeConfigFrom(
            __DIR__.'/config/apprentice.php',
            'apprentice'
        );
    }

    protected function registerMigrations()
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->publishes([
            __DIR__.'/database/migrations' => database_path('migrations'),
        ], 'apprentice-migrations');
    }

    protected function registerRoutes()
    {
        Route::prefix(config('apprentice.path'))
          ->middleware(['throttle:30,1', SignatureAuthentication::class])
          ->group(function () {
              Route::post('/accept-invitation', [InvitationController::class, 'accept']);
              Route::post('/execute', [SSECommandController::class, 'execute']);
              Route::post('/input', [SSECommandController::class, 'input']);
              Route::get('/output', [SSECommandController::class, 'output']);
          });
    }

    protected function registerCommands()
    {
        $this->commands([
            SetupCommand::class,
            InfoCommand::class,
        ]);
    }

    protected function registerSession()
    {
        app()->singleton(Session::class, function ($app) {
            return new Session;
        });
    }

    protected function registerApprenticeCommandHelper()
    {
        $this->app->bind('apprentice', function () {
            return $this->app->make(CommandHelper::class);
        });
    }
}
