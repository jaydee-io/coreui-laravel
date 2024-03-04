<?php

namespace JayDeeIO\CoreuiLaravel;

use Illuminate\Support\ServiceProvider;

class CoreuiLaravelServiceProvider extends ServiceProvider
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
        $this->configurePublishing();
        $this->configureCommands();
    }

    /**
     * Configure publishing for the package.
     *
     * @return void
     */
    protected function configurePublishing()
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../stubs/resources/scss/coreui.scss' => resource_path('scss/coreui.scss'),
            __DIR__.'/../stubs/resources/scss/coreui-custom-variables.scss' => resource_path('scss/coreui-custom-variables.scss'),
            __DIR__.'/../stubs/resources/scss/coreui-custom-maps.scss' => resource_path('scss/coreui-custom-maps.scss'),
            __DIR__.'/../stubs/resources/js/app.js' => resource_path('js/app.js'),
            __DIR__.'/../stubs/resources/views/app.blade.php' => resource_path('views/app.blade.php'),
            __DIR__.'/../stubs/resources/js/Pages/Welcome.vue' => resource_path('js/Pages/Welcome.vue'),
            __DIR__.'/../stubs/vite.config.js' => base_path('vite.config.js'),
            __DIR__.'/../stubs/jsconfig.json' => base_path('jsconfig.json'),
        ], 'coreui');
    }

    /**
     * Configure the commands offered by the application.
     *
     * @return void
     */
    protected function configureCommands()
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            Console\InstallCommand::class,
        ]);
    }
}
