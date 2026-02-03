<?php

namespace App\Providers;

use App\Support\Modules\CustomFileRepository;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Contracts\RepositoryInterface;

class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Override the module repository with our custom implementation
        $this->app->singleton(RepositoryInterface::class, function ($app) {
            $path = $app['config']->get('modules.paths.modules');

            return new CustomFileRepository($app, $path);
        });

        // // Also bind the 'modules' alias
        // $this->app->alias(RepositoryInterface::class, 'modules');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
