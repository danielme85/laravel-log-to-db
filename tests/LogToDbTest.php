<?php

use danielme85\LaravelLogToDB\LogToDB;
use Illuminate\Support\Facades\Log;

class LogToDbTest extends Orchestra\Testbench\TestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', ['--database' => 'mysql']);

        $this->beforeApplicationDestroyed(function () {
            $this->artisan('migrate:rollback', ['--database' => 'mysql']);
        });
    }
    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'mysql');
        $app['config']->set('database.connections',
            ['mysql' => [
                'driver' => 'mysql',
                'host' => '127.0.0.1',
                'port' => 3306,
                'database' => 'testing',
                'username' => 'travis',
                'password' => '',
                'charset' => 'utf8',
                'collation' => 'utf8_unicode_ci',
            ],
                'mongodb' => [
                    'driver'   => 'mongodb',
                    'host'     => '127.0.0.1',
                    'port'     => 27017,
                    'database' => 'testing',
                    'username' => '',
                    'password' => '',
                    'options'  => [
                        //'database' => 'admin' // sets the authentication database required by mongo 3
                    ]
                ],]
        );

        $app['config']->set('logging.default', 'stack');
        $app['config']->set('logging.channels', [
            'stack' => [
                'driver' => 'stack',
                'channels' => ['database', 'mongodb', 'single'],
            ],
            'database' => [
                'driver' => 'custom',
                'via' => danielme85\LaravelLogToDB\LogToDbHandler::class,
                'level' =>  'debug',
                'connection' => 'default',
                'collection' => 'log'
            ],
            'mongodb' => [
                'driver' => 'custom',
                'via' => danielme85\LaravelLogToDB\LogToDbHandler::class,
                'level' => 'debug',
                'connection' => 'mongodb',
                'collection' => 'log'
            ]
        ]);
    }

    /**
     * Get package providers.  At a minimum this is the package being tested, but also
     * would include packages upon which our package depends, e.g. Cartalyst/Sentry
     * In a normal app environment these would be added to the 'providers' array in
     * the config/app.php file.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            'danielme85\LaravelLogToDB\ServiceProvider',
            'Jenssegers\Mongodb\MongodbServiceProvider',
        ];
    }

    public function testClassInit() {
        $test = new LogToDB();
        $this->assertInstanceOf('danielme85\LaravelLogToDB\LogToDB', $test);
    }

    public function testLogLevels() {
        Log::debug("This is an test DEBUG log event");
        Log::info("This is an test INFO log event");
        Log::notice("This is an test NOTICE log event");
        Log::warning("This is an test WARNING log event");
        Log::error("This is an test ERROR log event");
        Log::critical("This is an test CRITICAL log event");
        Log::alert("This is an test ALERT log event");
        Log::emergency("This is an test EMERGENCY log event");

        //Check mysql
        $logReader = LogToDB::model()::all()->toArray();
        $logReaderMongoDB = LogToDB::model('mongodb')::all()->toArray();
        $this->assertNotEmpty($logReader);
        $this->assertNotEmpty($logReaderMongoDB);
        $this->assertCount(8, $logReader);
        $this->assertCount(8, $logReaderMongoDB);

    }

    public function testCleanup() {
        LogToDB::model()::truncate();
        LogToDB::model('mongodb')::truncate();

        $this->assertEmpty(LogToDB::model()::all()->toArray());
        $this->assertEmpty(LogToDB::model('mongodb')::all()->toArray());
    }
}