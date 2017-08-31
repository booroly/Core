<?php

namespace TypiCMS\Modules\Core\Providers;

use Exception;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use TypiCMS\Modules\Core\Commands\CacheKeyPrefix;
use TypiCMS\Modules\Core\Commands\Create;
use TypiCMS\Modules\Core\Commands\Database;
use TypiCMS\Modules\Core\Commands\Install;
use TypiCMS\Modules\Core\Commands\Publish;
use TypiCMS\Modules\Core\Composers\LocaleComposer;
use TypiCMS\Modules\Core\Composers\LocalesComposer;
use TypiCMS\Modules\Core\Composers\MasterViewComposer;
use TypiCMS\Modules\Core\Composers\SidebarViewCreator;
use TypiCMS\Modules\Core\Services\TypiCMS;
use TypiCMS\Modules\Core\Services\Upload\FileUpload;

class ModuleProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return null
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views/', 'core');

        $this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/core'),
            __DIR__.'/../resources/views/errors' => base_path('resources/views/errors'),
        ], 'views');

        $this->publishes([
            __DIR__.'/../resources/assets' => base_path('resources/assets'),
            __DIR__.'/../../public' => public_path(),
        ], 'assets');

        /*
        |--------------------------------------------------------------------------
        | Sidebar view creator.
        |--------------------------------------------------------------------------
        */
        $this->app->view->creator('core::admin._sidebar', SidebarViewCreator::class);

        /*
        |--------------------------------------------------------------------------
        | View composers.
        |--------------------------------------------------------------------------
        */
        $this->app->view->composers([
            MasterViewComposer::class => '*',
            LocaleComposer::class => '*::public.*',
            LocalesComposer::class => '*::admin.*',
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return null
     */
    public function register()
    {
        $app = $this->app;

        /*
         * Register route service provider
         */
        $app->register(RouteServiceProvider::class);

        /*
         * Collection extensions
         */
        $app->register(CollectionExtensions::class);

        /*
        |--------------------------------------------------------------------------
        | Init list of modules.
        |--------------------------------------------------------------------------
        */
        Config::set('typicms.modules', []);

        /*
        |--------------------------------------------------------------------------
        | TypiCMS utilities.
        |--------------------------------------------------------------------------
        */
        $this->app->singleton('typicms', function () {
            return new TypiCMS();
        });

        /*
        |--------------------------------------------------------------------------
        | TypiCMS upload service.
        |--------------------------------------------------------------------------
        */
        $this->app->singleton('upload.file', function () {
            return new FileUpload();
        });

        $this->commands([
            CacheKeyPrefix::class,
            Create::class,
            Database::class,
            Install::class,
            Publish::class,
        ]);

        $this->registerModuleRoutes();
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }

    /**
     * Get routes from pages.
     *
     * @return null
     */
    private function registerModuleRoutes()
    {
        $this->app->singleton('typicms.routes', function (Application $app) {
            try {
                return $app->make('Pages')->getForRoutes();
            } catch (Exception $e) {
                return [];
            }
        });
    }
}
