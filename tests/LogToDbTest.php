<?php

use danielme85\LaravelLogToDB\LogToDB;
use danielme85\LaravelLogToDB\Models\DBLogException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use danielme85\LaravelLogToDB\Jobs\SaveNewLogEvent;

class LogToDbTest extends Orchestra\Testbench\TestCase
{
    protected $migrated = false;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (!$this->migrated) {
            $this->loadMigrationsFrom(__DIR__.'/../src/migrations');
            if ($this->artisan('migrate', [
                '--database' => 'mysql'])) {
                $this->migrated = true;
            }
        }
    }

    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'/../', '.env.testing');
        $dotenv->load();

        $app['config']->set('database.default', 'mysql');
        $app['config']->set('database.connections',
            ['mysql' => [
                'driver' => 'mysql',
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', 3306),
                'database' => env('DB_DATABASE', 'testing'),
                'username' => env('DB_USER', 'root'),
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
                        'database' => 'admin' // sets the authentication database required by mongo 3
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
                'level' => 'debug',
                'connection' => 'default',
                'collection' => 'log',
                'max_records' => 10,
                'max_hours' => 1,
                'processors' => [
                    Monolog\Processor\HostnameProcessor::class,
                    Monolog\Processor\MemoryUsageProcessor::class,
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

        $app['config']->set('logtodb', include __DIR__.'/../src/config/logtodb.php');
    }

    /**
     * Get package providers.  At a minimum this is the package being tested, but also
     * would include packages upon which our package depends, e.g. Cartalyst/Sentry
     * In a normal app environment these would be added to the 'providers' array in
     * the config/app.php file.
     *
     * @param \Illuminate\Foundation\Application $app
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
    public function testClassInit()
    {
        $this->assertInstanceOf(LogToDB::class, app('laravel-log-to-db'));
        $this->assertInstanceOf(LogToDB::class, new LogToDB());

        //Class works, now let's cleanup possible failed test
        LogToDB::model()->truncate();
        LogToDB::model('mongodb')->truncate();
    }

    /**
     * Run basic log levels
     *
     * @group basic
     */
    public function testLogLevels()
    {
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
     *
     * @group events
     * @throws Exception
     */
    public function testMessageLoggedEvent()
    {
        $this->expectsEvents([\Illuminate\Log\Events\MessageLogged::class]);
        Log::debug("This is to trigger a log event.");
    }

    /**
     * @group context
     */
    public function testContext()
    {
        Log::error("Im trying to add some context", ['whatDis?' => 'dis some context, should always be array']);
        $log = LogToDB::model()->where('message', '=' , 'Im trying to add some context')->first();
        $this->assertNotEmpty($log);
        $this->assertStringContainsString("Im trying to add some context", $log->message);
        $this->assertIsArray($log->context);
    }

    /**
     * Check to see if processors are adding extra content.
     *
     * @group basic
     */
    public function testProcessors()
    {
        Log::info("hello");
        $log = LogToDB::model()->orderBy('created_at', 'desc')->first()->toArray();
        $this->assertNotEmpty($log['extra']);
        $this->assertNotEmpty($log['extra']['memory_usage']);
        $this->assertNotEmpty($log['extra']['php_version']);
        $this->assertNotEmpty($log['extra']['hostname']);
    }

    /**
     * Test logging to specific channels
     *
     * @group advanced
     */
    public function testLoggingToChannels()
    {
        //Test limited config, with limited rows and level
        Log::channel('limited')->debug("This message should not be stored because DEBUG is LOWER then WARNING");
        $this->assertEmpty(LogToDB::model('limited')->where('channel', 'limited')->where('level_name', 'DEBUG')->get());

        //Test limited config, with limited rows and level
        Log::channel('limited')->warning("This message should be stored because WARNING = WARNING");
        $this->assertNotEmpty(LogToDB::model('limited')->where('channel', 'limited')->where('level_name', 'WARNING')->get());

    }

    /**
     * Test an exception error.
     *
     * @group exception
     */
    public function testException()
    {
        $e = new Symfony\Component\HttpKernel\Exception\BadRequestHttpException("This is a fake 500 error", null, 500, ['fake-header' => 'value']);
        Log::warning("Error", ['exception' => $e, 'more' => 'infohere']);
        $log = LogToDB::model()->where('message', 'Error')->first();
        $this->assertNotEmpty($log->context);

        $empty = new \Mockery\Exception();
        Log::warning("Error", ['exception' => $empty]);
        $log = LogToDB::model()->where('message', 'Error')->orderBy('id', 'DESC')->first();
        $this->assertNotEmpty($log);

        $this->expectException(DBLogException::class);
        throw new DBLogException('Dont log this');
    }
    
    /**
     * Test exception when expected format is wrong.
     *
     * @group exception
     */
    public function testExceptionWrongFormat()
    {
        $e = [
            'message' => 'Array instead exception',
            'code'    => 0,
            'file'    => __FILE__,
            'line'    => __LINE__,
            'trace'   => debug_backtrace(),
        ];
        Log::warning("Error", ['exception' => $e, 'more' => 'infohere']);
        $log = LogToDB::model()->where('message', 'Error')->first();
        $this->assertNotEmpty($log->context);
    }

    /**
     *
     *  @group exception
     */
    public function testExceptionIgnore()
    {
        $this->assertCount(0, LogToDB::model()->where('message', '=', 'Dont log this')->get()->toArray());
    }

    /**
     * Test queuing the log events.
     *
     * @group queue
     */
    public function testQueue()
    {
        Queue::fake();

        config()->set('logtodb.queue', true);

        Log::info("I'm supposed to be added to the queue...");
        Log::warning("I'm supposed to be added to the queue...");
        Log::debug("I'm supposed to be added to the queue...");

        Queue::assertPushed(SaveNewLogEvent::class, 6);

        config()->set('logtodb.queue_name', 'logHandler');

        Log::info("I'm supposed to be added to the queue...");
        Log::warning("I'm supposed to be added to the queue...");
        Log::debug("I'm supposed to be added to the queue...");

        config()->set('logtodb.queue_name', null);
        config()->set('logtodb.queue_connection', 'default');

        Log::info("I'm supposed to be added to the queue...");
        Log::warning("I'm supposed to be added to the queue...");
        Log::debug("I'm supposed to be added to the queue...");

        config()->set('logtodb.queue_name', 'logHandler');

        Log::info("I'm supposed to be added to the queue...");
        Log::warning("I'm supposed to be added to the queue...");
        Log::debug("I'm supposed to be added to the queue...");

        Queue::assertPushed(SaveNewLogEvent::class, 24);

        config()->set('logtodb.queue', false);

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
    public function testModelInteraction()
    {
        LogToDB::model()->truncate();
        LogToDB::model('mongodb')->truncate();

        for ($i=1; $i<=10; $i++) {
            Log::debug("This is debug log message...");
        }
        for ($i=1; $i<=10; $i++) {
            Log::info("This is info log message...");
        }


        $model = LogToDB::model();

        $log = $model->first();
        $this->assertIsNumeric($log->id);
        $this->assertIsString($log->message);
        $this->assertIsString($log->channel);
        $this->assertIsNumeric($log->level);
        $this->assertIsString($log->level_name);
        $this->assertIsNumeric($log->unix_time);
        $this->assertIsString($log->datetime);
        $this->assertIsArray($log->extra);
        $this->assertNotEmpty($log->created_at);
        $this->assertNotEmpty($log->updated_at);
        $this->assertDatabaseCount('log', 20);


        //Get all
        $all = $model->get();
        $this->assertNotEmpty($all->toArray());
        //Get Debug
        $logs = $model->where('level_name', '=', 'DEBUG')->get()->toArray();
        $this->assertNotEmpty($logs);
        $this->assertEquals('DEBUG', $logs[0]['level_name']);
        $this->assertCount(10, $logs);

        $model = LogToDB::model('database');
        //Get all
        $all = $model->get();
        $this->assertNotEmpty($all->toArray());
        //Get Debug
        $logs = $model->where('level_name', '=', 'DEBUG')->get()->toArray();
        $this->assertNotEmpty($logs);
        $this->assertEquals('DEBUG', $logs[0]['level_name']);
        $this->assertCount(10, $logs);


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
        $this->assertCount(10, $logs);

        //Same tests for mongoDB
        $modelMongo = LogToDB::model('mongodb');
        //Get all
        $all = $modelMongo->get();
        $this->assertNotEmpty($all->toArray());
        //Get Debug
        $logs = $modelMongo->where('level_name', '=', 'DEBUG')->get()->toArray();
        $this->assertNotEmpty($logs);
        $this->assertEquals('DEBUG', $logs[0]['level_name']);
        $this->assertCount(10, $logs);

        //Same tests for mongoDB
        $modelMongo = LogToDB::model('mongodb', 'mongodb', 'log');
        //Get all
        $all = $modelMongo->get();
        $this->assertNotEmpty($all->toArray());
        //Get Debug
        $logs = $modelMongo->where('level_name', '=', 'DEBUG')->get()->toArray();
        $this->assertNotEmpty($logs);
        $this->assertEquals('DEBUG', $logs[0]['level_name']);
        $this->assertCount(10, $logs);

        //Same tests for mongoDB
        $modelMongo = LogToDB::model(null, 'mongodb');
        //Get all
        $all = $modelMongo->get();
        $this->assertNotEmpty($all->toArray());
        //Get Debug
        $logs = $modelMongo->where('level_name', '=', 'DEBUG')->get()->toArray();
        $this->assertNotEmpty($logs);
        $this->assertEquals('DEBUG', $logs[0]['level_name']);
        $this->assertCount(10, $logs);
    }

    /**
     * $group model
     */
    public function testStandAloneModels()
    {
        Log::info("This is a info log message...");
        $this->assertNotEmpty(LogSql::get()->toArray());
        $this->assertNotEmpty(LogMongo::get()->toArray());
    }

    /**
     * @group model
     */
    public function testCustomModel()
    {
        config()->set('logtodb.model', \danielme85\LaravelLogToDB\Tests\CustomEloquentModel::class);
        $this->expectException(\danielme85\LaravelLogToDB\Models\DBLogException::class);
        $this->expectExceptionMessage('This is on a custom model class');
        Log::info('This is on a custom model class');
        $this->assertStringContainsString('This is on a custom model class', LogToDB::model()->latest('id')->first()->message);
    }

    /**
     * Test the cleanup functions.
     *
     * @group cleanup
     */
    public function testRemoves()
    {
        $this->assertFalse(LogToDB::model()->removeOldestIfMoreThan(1000));

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
    public function testFinalCleanup()
    {
        LogToDB::model()->truncate();
        LogToDB::model('mongodb')->truncate();

        $this->assertEmpty(LogToDB::model()->get()->toArray());
        $this->assertEmpty(LogToDB::model('mongodb')->get()->toArray());
        $this->assertEmpty(LogToDB::model('limited')->get()->toArray());
        $this->assertEmpty(LogToDB::model('database')->get()->toArray());

        $this->artisan('migrate:rollback', ['--database' => 'mysql']);
    }
}
