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
                'via' => danielme85\LaravelLogToDB\LogToDbHandler::class,
                'level' =>  'debug',
                'connection' => 'default',
                'collection' => 'log',
                'max_records' => 10,
                'max_hours' => 1,
                'processors' => [
                    Monolog\Processor\HostnameProcessor::class,
                    danielme85\LaravelLogToDB\Processors\PhpVersionProcessor::class
                ]
            ],
            'mongodb' => [
                'driver' => 'custom',
                'via' => danielme85\LaravelLogToDB\LogToDbHandler::class,
                'level' => 'debug',
                'connection' => 'mongodb',
                'collection' => 'log',
                'max_records' => 10,
                'max_hours' => 1,
                'processors' => [
                    Monolog\Processor\HostnameProcessor::class
                ]
            ],
            'limited' => [
                'driver' => 'custom',
                'via' => danielme85\LaravelLogToDB\LogToDbHandler::class,
                'level' => 'warning',
                'detailed' => false,
                'max_records' => false,
                'max_hours' => false,
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

    /**
     * Basic test to see if class can be instanced.
     *
     * @group basic
     */
    public function testClassInit() {
        $test = new LogToDB();
        $this->assertInstanceOf('danielme85\LaravelLogToDB\LogToDB', $test);

        //Class works, now let's cleanup possible failed test
        LogToDB::model()->truncate();
        LogToDB::model('mongodb')->truncate();
    }

    /**
     * Run basic log levels
     *
     * @group basic
     */
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
        $logReaderSpecific = LogToDB::model('database', 'mysql', 'LogSql')->get()->toArray();
        $this->assertCount(8, $logReader);
        $this->assertCount(8, $logReaderMongoDB);
        $this->assertCount(8, $logReaderSpecific);
    }

    /**
     * Check to see if processors are adding extra content.
     *
     * @group basic
     */
    public function testProcessors()
    {
        $log = LogToDB::model()->orderBy('created_at', 'desc')->first()->toArray();
        $this->assertNotEmpty($log['extra']);
        $this->assertNotEmpty($log['extra']['php_version']);
        $this->assertNotEmpty($log['extra']['hostname']);
    }

    /**
     * Test logging to specific channels
     *
     * @group advanced
     */
    public function testLoggingToChannels() {
        //Test limited config, with limited rows and level
        Log::channel('limited')->debug("This message should not be stored because DEBUG is LOWER then WARNING");
        $this->assertEmpty(LogToDB::model('limited')->where('channel', 'limited')->where('level_name', 'DEBUG')->get()->toArray());

        //Test limited config, with limited rows and level
        Log::channel('limited')->warning("This message should be stored because WARNING = WARNING");
        $this->assertNotEmpty(LogToDB::model('limited')->where('channel', 'limited')->where('level_name', 'WARNING')->get()->toArray());
    }

    /**
     * Test an exception error.
     *
     * @group advanced
     */
    public function testException() {
        $e = new Symfony\Component\HttpKernel\Exception\BadRequestHttpException("This is a fake 500 error", null, 500, ['fake-header' => 'value']);
        Log::warning("Error", ['exception' => $e, 'more' => 'infohere']);
        $log = LogToDB::model()->where('message', 'Error')->first();
        $this->assertNotEmpty($log->context);
    }

    /**
     * Test queuing the log events.
     *
     * @group queue
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
     * Test save new log event job
     *
     * @group job
     */
    public function testSaveNewLogEventJob()
    {
        $logToDb = new LogToDB();
        $record = [
            'message' => 'job-test',
            'context' => [],
            'level' => 200,
            'level_name' => 'INFO',
            'channel' => 'local',
            'datetime' => new Monolog\DateTimeImmutable(true),
            'extra' => [],
            'formatted' => "[2019-10-04T17:26:38.446827+00:00] local.INFO: test [] []\n"
        ];

        $job = new SaveNewLogEvent($logToDb, $record);
        $job->handle();

        $this->assertNotEmpty($logToDb->model()->where('message', '=', 'job-test')->get());
    }


    /**
     * Test model interaction
     *
     * @group model
     */
    public function testModelInteraction() {
        $model = LogToDB::model();
        //Get all
        $all = $model->get();
        $this->assertNotEmpty($all->toArray());
        //Get Debug
        $logs = $model->where('level_name', '=', 'DEBUG')->get()->toArray();
        $this->assertNotEmpty($logs);
        $this->assertEquals('DEBUG', $logs[0]['level_name']);

        $model = LogToDB::model('database');
        //Get all
        $all = $model->get();
        $this->assertNotEmpty($all->toArray());
        //Get Debug
        $logs = $model->where('level_name', '=', 'DEBUG')->get()->toArray();
        $this->assertNotEmpty($logs);
        $this->assertEquals('DEBUG', $logs[0]['level_name']);

        $model = LogToDB::model(null, 'mysql');
        //Get all
        $all = $model->get();
        $this->assertNotEmpty($all->toArray());
        //Get Debug
        $logs = $model->where('level_name', '=', 'DEBUG')->get()->toArray();
        $this->assertNotEmpty($logs);
        $this->assertEquals('DEBUG', $logs[0]['level_name']);

        $model = LogToDB::model('database', 'mysql', 'log');
        //Get all
        $all = $model->get();
        $this->assertNotEmpty($all->toArray());
        //Get Debug
        $logs = $model->where('level_name', '=', 'DEBUG')->get()->toArray();
        $this->assertNotEmpty($logs);
        $this->assertEquals('DEBUG', $logs[0]['level_name']);

        //Same tests for mongoDB
        $modelMongo = LogToDB::model('mongodb');
        //Get all
        $all = $modelMongo->get();
        $this->assertNotEmpty($all->toArray());
        //Get Debug
        $logs = $modelMongo->where('level_name', '=', 'DEBUG')->get()->toArray();
        $this->assertNotEmpty($logs);
        $this->assertEquals('DEBUG', $logs[0]['level_name']);

        //Same tests for mongoDB
        $modelMongo = LogToDB::model('mongodb', 'mongodb', 'log');
        //Get all
        $all = $modelMongo->get();
        $this->assertNotEmpty($all->toArray());
        //Get Debug
        $logs = $modelMongo->where('level_name', '=', 'DEBUG')->get()->toArray();
        $this->assertNotEmpty($logs);
        $this->assertEquals('DEBUG', $logs[0]['level_name']);

        //Same tests for mongoDB
        $modelMongo = LogToDB::model(null, 'mongodb');
        //Get all
        $all = $modelMongo->get();
        $this->assertNotEmpty($all->toArray());
        //Get Debug
        $logs = $modelMongo->where('level_name', '=', 'DEBUG')->get()->toArray();
        $this->assertNotEmpty($logs);
        $this->assertEquals('DEBUG', $logs[0]['level_name']);

    }

    public function testStandAloneModels() {
        $this->assertNotEmpty(LogSql::get()->toArray());
        $this->assertNotEmpty(LogMongo::get()->toArray());
    }

    /**
     * Test the cleanup functions.
     *
     * @group cleanup
     */
    public function testRemoves() {
        Log::debug("This is an test DEBUG log event");
        Log::info("This is an test INFO log event");
        Log::notice("This is an test NOTICE log event");

        //sleep to pass time for record cleanup testing based on time next.
        sleep(1);

        $this->assertTrue(LogToDB::model()->removeOldestIfMoreThan(2));
        $this->assertEquals(2, LogToDB::model()->count());
        $this->assertTrue(LogToDB::model()->removeOlderThan(date('Y-m-d H:i:s')));
        $this->assertEquals(0, LogToDB::model()->count());

        //Same tests on mongodb
        $this->assertTrue(LogToDB::model('mongodb')->removeOldestIfMoreThan(2));
        $this->assertEquals(2, LogToDB::model('mongodb')->count());
        $this->assertTrue(LogToDB::model('mongodb')->removeOlderThan(date('Y-m-d H:i:s')));
        $this->assertEquals(0, LogToDB::model('mongodb')->count());


        //test wrappers for silly spelling
        Log::debug("This is an test DEBUG log event");
        Log::info("This is an test INFO log event");
        Log::notice("This is an test NOTICE log event");

        //sleep to pass time for record cleanup testing based on time next.
        sleep(1);

        $this->assertTrue(LogToDB::model()->removeOldestIfMoreThen(2));
        $this->assertEquals(2, LogToDB::model()->count());
        $this->assertTrue(LogToDB::model()->removeOlderThen(date('Y-m-d H:i:s')));
        $this->assertEquals(0, LogToDB::model()->count());

        //Same tests on mongodb
        $this->assertTrue(LogToDB::model('mongodb')->removeOldestIfMoreThen(2));
        $this->assertEquals(2, LogToDB::model('mongodb')->count());
        $this->assertTrue(LogToDB::model('mongodb')->removeOlderThen(date('Y-m-d H:i:s')));
        $this->assertEquals(0, LogToDB::model('mongodb')->count());

    }

    /**
     * Test the CleanerUpper
     *
     * @group cleanerUpper
     */
    public function testCleanerUpper()
    {
        //Add bunch of old records
        for ($i = 0; $i < 20; $i++) {
            $log = LogToDB::model();
            $thePast = \Carbon\Carbon::now()->subHours(24);

            $log->message = "This is fake test number: {$i}";
            $log->channel = 'test';
            $log->level = 100;
            $log->level_name = 'DEBUG';
            $log->unix_time = $thePast->unix();
            $log->datetime = new \Monolog\DateTimeImmutable(time());
            $log->created_at = $thePast->toDateTimeString();
            $log->updated_at = $thePast->toDateTimeString();
            $log->save();
        }

        //Add bunch of old records
        for ($i = 0; $i < 20; $i++) {
            $log = LogToDB::model('mongodb');
            $thePast = \Carbon\Carbon::now()->subHours(24);

            $log->message = "This is fake test number: {$i}";
            $log->channel = 'test';
            $log->level = 100;
            $log->level_name = 'DEBUG';
            $log->unix_time = $thePast->unix();
            $log->datetime = new \Monolog\DateTimeImmutable(time());
            $log->created_at = $thePast->toDateTimeString();
            $log->updated_at = $thePast->toDateTimeString();
            $log->save();
        }

        //Add 5 new records
        for ($i = 0; $i < 5; $i++) {
            Log::debug("This is an test DEBUG log event number: {$i}");
        }

        $this->assertEquals(25, LogToDB::model()->count());
        $this->assertEquals(25, LogToDB::model('mongodb')->count());

        //Run cleanup command
        $this->artisan('log:delete')->assertExitCode(0);

        $this->assertEquals(5, LogToDB::model()->count());
        $this->assertEquals(5, LogToDB::model('mongodb')->count());

        //Add 10 new records
        for ($i = 0; $i < 10; $i++) {
            Log::debug("This is an test DEBUG log event number: {$i}");
        }

        //Run cleanup command
        $this->artisan('log:delete')->assertExitCode(0);

        $this->assertEquals(10, LogToDB::model()->count());
        $this->assertEquals(10, LogToDB::model('mongodb')->count());
    }

    /**
     * Clear all data from the test.
     *
     * @group cleanerUpper
     */
    public function testFinalCleanup() {
        LogToDB::model()->truncate();
        LogToDB::model('mongodb')->truncate();

        $this->assertEmpty(LogToDB::model()->get()->toArray());
        $this->assertEmpty(LogToDB::model('mongodb')->get()->toArray());
        $this->assertEmpty(LogToDB::model('limited')->get()->toArray());
        $this->assertEmpty(LogToDB::model('database')->get()->toArray());
    }
}