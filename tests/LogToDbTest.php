<?php

use danielme85\LaravelLogToDB\LogToDB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use danielme85\LaravelLogToDB\Jobs\SaveNewLogEvent;

class LogToDbTest extends Orchestra\Testbench\TestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', ['--database' => 'mysql']);

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
                'channels' => ['database', 'mongodb'],
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
            ],
            'limited' => [
                'driver' => 'custom',
                'via' => danielme85\LaravelLogToDB\LogToDbHandler::class,
                'level' => 'warning',
                'detailed' => false,
                'max_rows' => 10,
                'name' => 'limited',
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
        $logReader = LogToDB::model()->get()->toArray();
        $logReaderMongoDB = LogToDB::model('mongodb')->get()->toArray();
        $logReaderSpecific = LogToDB::model('database', 'mysql', 'log')->get()->toArray();
        $this->assertCount(8, $logReader);
        $this->assertCount(8, $logReaderMongoDB);
        $this->assertCount(8, $logReaderSpecific);
    }

    public function testLoggingToChannels() {
        //Test limited config, with limited rows and level
        Log::channel('limited')->debug("This message should not be stored because DEBUG is LOWER then WARNING");
        $this->assertEmpty(LogToDB::model('limited')->where('channel', 'limited')->where('level_name', 'DEBUG')->get()->toArray());

        //Test limited config, with limited rows and level
        Log::channel('limited')->warning("This message should be stored because WARNING = WARNING");
        $this->assertNotEmpty(LogToDB::model('limited')->where('channel', 'limited')->where('level_name', 'WARNING')->get()->toArray());
    }

    public function testException() {
        $e = new Symfony\Component\HttpKernel\Exception\BadRequestHttpException("This is a fake 500 error", null, 500, ['fake-header' => 'value']);
        Log::warning("Error", ['exception' => $e, 'more' => 'infohere']);
        $log = LogToDB::model()->where('message', 'Error')->first();
        $this->assertNotEmpty($log->context);
    }

    /**
     * @group queue
     *
     */
    public function testQueue() {
        Queue::fake();

        config()->set('logtodb.queue_db_saves', true);

        Log::info("I'm supposed to be added to the queue...");
        Log::warning("I'm supposed to be added to the queue...");
        Log::debug("I'm supposed to be added to the queue...");

        Queue::assertPushed(SaveNewLogEvent::class, 6);

        config()->set('logtodb.queue_db_queue', 'logHandler');
        config()->set('logtodb.queue_db_connection', 'default');

        Log::info("I'm supposed to be added to the queue...");
        Log::warning("I'm supposed to be added to the queue...");
        Log::debug("I'm supposed to be added to the queue...");

        Queue::assertPushed(SaveNewLogEvent::class, 12);
    }

    /**
     * @group cleanup
     *
     */
    public function testRemoves() {
        $this->assertTrue(LogToDB::model()->removeOldestIfMoreThen(1));
        $this->assertFalse(LogToDB::model()->removeOlderThen(date('Y-m-d')));

        $this->assertTrue(LogToDB::model('mongodb')->removeOldestIfMoreThen(1));
        $this->assertFalse(LogToDB::model('mongodb')->removeOlderThen(date('Y-m-d')));
    }

    public function testCleanup() {
        LogToDB::model()->truncate();
        LogToDB::model('mongodb')->truncate();

        $this->assertEmpty(LogToDB::model()->get()->toArray());
        $this->assertEmpty(LogToDB::model('mongodb')->get()->toArray());
        $this->assertEmpty(LogToDB::model('limited')->get()->toArray());
        $this->assertEmpty(LogToDB::model('database')->get()->toArray());
    }
}