<?php

namespace danielme85\LaravelLogToDB;

use danielme85\LaravelLogToDB\Commands\LogCleanerUpper;
use Illuminate\Support\ServiceProvider as Provider;

/**
 * Class ServiceProvider
 *
 * @package danielme85\LaravelLogToDB
 */
class ServiceProvider extends Provider {
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //config path is missing in Lumen
        //https://gist.github.com/mabasic/21d13eab12462e596120
        if (!function_exists('config_path')) {
            function config_path($path = '')
            {
                return app()->basePath() . '/config' . ($path ? '/' . $path : $path);
            }
        }

        $this->loadMigrationsFrom(__DIR__.'/migrations');
        $this->mergeConfigFrom(__DIR__.'/config/logtodb.php', 'logtodb');
        $this->publishes([
            __DIR__.'/config/logtodb.php' => config_path('logtodb.php'),
        ]);
        if ($this->app->runningInConsole()) {
            $this->commands([
                LogCleanerUpper::class,
            ]);
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('laravel-log-to-db', function() {
            return new LogToDB();
        });
    }
}