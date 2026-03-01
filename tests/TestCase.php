<?php

namespace Tests;

use danielme85\LaravelLogToDB\LogToDbHandler;
use danielme85\LaravelLogToDB\Processors\PhpVersionProcessor;
use Dotenv\Dotenv;
use Monolog\Processor\HostnameProcessor;
use Monolog\Processor\MemoryUsageProcessor;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function defineEnvironment($app)
    {
        $dotenv = Dotenv::createImmutable(__DIR__.'/../', '.env.testing');
        $dotenv->load();

        $app['config']->set('database.default', 'mysql');
        $app['config']->set('database.connections', [
            'mysql' => [
                'driver' => 'mysql',
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', 3306),
                'database' => env('DB_DATABASE', 'testing'),
                'username' => env('DB_USERNAME', 'root'),
                'password' => env('DB_PASSWORD', ''),
                'charset' => 'utf8',
                'collation' => 'utf8_unicode_ci',
            ],
            'mongodb' => [
                'driver' => 'mongodb',
                'host' => env('MDB_HOST', '127.0.0.1'),
                'port' => env('MDB_PORT', 27017),
                'database' => env('MDB_DATABASE', 'testing'),
                'username' => env('MDB_USER', ''),
                'password' => env('MDB_PASSWORD', ''),
                'options' => [
                    'database' => 'admin'
                ]
            ],
        ]);

        $app['config']->set('logging.default', 'stack');
        $app['config']->set('logging.channels', [
            'stack' => [
                'driver' => 'stack',
                'channels' => ['database', 'mongodb'],
            ],
            'database' => [
                'driver' => 'custom',
                'via' => LogToDbHandler::class,
                'level' => 'debug',
                'connection' => 'default',
                'collection' => 'log',
                'max_records' => 10,
                'max_hours' => 1,
                'processors' => [
                    HostnameProcessor::class,
                    MemoryUsageProcessor::class,
                    PhpVersionProcessor::class
                ]
            ],
            'mongodb' => [
                'driver' => 'custom',
                'via' => LogToDbHandler::class,
                'level' => 'debug',
                'connection' => 'mongodb',
                'collection' => 'log',
                'max_records' => 10,
                'max_hours' => 1,
                'processors' => [
                    HostnameProcessor::class
                ]
            ],
            'limited' => [
                'driver' => 'custom',
                'via' => LogToDbHandler::class,
                'level' => 'warning',
                'detailed' => false,
                'max_records' => false,
                'max_hours' => false,
                'name' => 'limited',
            ]
        ]);

        $app['config']->set('logtodb', include __DIR__.'/../src/config/logtodb.php');
    }

    protected function getPackageProviders($app)
    {
        return [
            \danielme85\LaravelLogToDB\ServiceProvider::class,
            \MongoDB\Laravel\MongoDBServiceProvider::class,
        ];
    }
}
