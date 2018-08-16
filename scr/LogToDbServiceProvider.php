<?php

namespace danielme85\LaravelLogToDB;

use Illuminate\Support\ServiceProvider;

/**
 * Class LogToDbServiceProvider
 *
 * @package danielme85\LaravelLogToDB
 */
class LogToDbServiceProvider extends ServiceProvider {
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
        $this->mergeConfigFrom(__DIR__.'/config/logtodb.php', 'logtodb');
        /*
        $this->publishes([
            __DIR__.'/config/larastats.php' => config_path('larastats.php'),
        ]);
        */
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('laravel-log-to-db', function() {
            return new LogToDb();
        });
    }
}