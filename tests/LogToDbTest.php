<?php

use danielme85\LaravelLogToDB\Jobs\SaveNewLogEvent;
use danielme85\LaravelLogToDB\LogToDB;
use danielme85\LaravelLogToDB\Models\DBLogException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use TestModels\CustomEloquentModel;
use TestModels\LogSql;
use Monolog\LogRecord;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class LogToDbTest extends Tests\TestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadMigrationsFrom(__DIR__.'/../src/migrations');

        // Truncate tables so each test starts clean
        LogToDB::model()->truncate();
        LogToDB::model('mongodb')->truncate();
    }

    /**
     * Define database migrations.
     *
     * @return void
     */
    protected function defineDatabaseMigrations()
    {
        $this->artisan('migrate', ['--database' => 'mysql'])->run();
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
        $e = new BadRequestHttpException("This is a fake 500 error", null, 500, ['fake-header' => 'value']);
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
        $record = new LogRecord(
            datetime: new \Monolog\DateTimeImmutable(true),
            channel: 'local',
            level: \Monolog\Level::Info,
            message: 'job-test',
            context: [],
            extra: [],
            formatted: "[2019-10-04T17:26:38.446827+00:00] local.INFO: test [] []\n",
        );

        $job = new SaveNewLogEvent($record);
        $job->handle();

        $this->assertNotEmpty($logToDb->model()->where('message', '=', 'job-test')->get());
    }


    /**
     * Test model attribute types from a single log record.
     *
     * @group model
     */
    public function testModelAttributeTypes()
    {
        Log::debug("This is a test log message for attribute type checking");

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
    }

    /**
     * Test model access by channel name.
     *
     * @group model
     */
    public function testModelAccessByChannel()
    {
        for ($i = 1; $i <= 5; $i++) {
            Log::debug("Channel access test message");
        }

        // MySQL via 'database' channel
        $model = LogToDB::model('database');
        $logs = $model->where('level_name', '=', 'DEBUG')->get()->toArray();
        $this->assertNotEmpty($logs);
        $this->assertEquals('DEBUG', $logs[0]['level_name']);
        $this->assertCount(5, $logs);

        // MongoDB via 'mongodb' channel
        $modelMongo = LogToDB::model('mongodb');
        $logs = $modelMongo->where('level_name', '=', 'DEBUG')->get()->toArray();
        $this->assertNotEmpty($logs);
        $this->assertEquals('DEBUG', $logs[0]['level_name']);
        $this->assertCount(5, $logs);
    }

    /**
     * Test model access by connection name.
     *
     * @group model
     */
    public function testModelAccessByConnection()
    {
        for ($i = 1; $i <= 5; $i++) {
            Log::debug("Connection access test message");
        }

        // MySQL via connection
        $model = LogToDB::model(null, 'mysql');
        $all = $model->get();
        $this->assertNotEmpty($all->toArray());

        // MongoDB via connection
        $modelMongo = LogToDB::model(null, 'mongodb');
        $logs = $modelMongo->where('level_name', '=', 'DEBUG')->get()->toArray();
        $this->assertNotEmpty($logs);
        $this->assertEquals('DEBUG', $logs[0]['level_name']);
        $this->assertCount(5, $logs);
    }

    /**
     * Test model access by channel, connection, and collection combined.
     *
     * @group model
     */
    public function testModelAccessByChannelConnectionCollection()
    {
        for ($i = 1; $i <= 5; $i++) {
            Log::debug("Combined access test message");
        }

        // MySQL with all params
        $model = LogToDB::model('database', 'mysql', 'log');
        $logs = $model->where('level_name', '=', 'DEBUG')->get()->toArray();
        $this->assertNotEmpty($logs);
        $this->assertEquals('DEBUG', $logs[0]['level_name']);
        $this->assertCount(5, $logs);

        // MongoDB with all params
        $modelMongo = LogToDB::model('mongodb', 'mongodb', 'log');
        $logs = $modelMongo->where('level_name', '=', 'DEBUG')->get()->toArray();
        $this->assertNotEmpty($logs);
        $this->assertEquals('DEBUG', $logs[0]['level_name']);
        $this->assertCount(5, $logs);
    }

    /**
     * $group model
     */
    public function testStandAloneModels()
    {
        Log::info("This is a info log message...");

        $modelMongo = LogToDB::model(null, 'mongodb');

        $this->assertNotEmpty(LogSql::get()->toArray());
        $this->assertNotEmpty($modelMongo->get()->toArray());
    }

    /**
     * @group model
     */
    public function testCustomModel()
    {
        config()->set('logtodb.model', CustomEloquentModel::class);
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

        // Insert records with past unix_time directly instead of sleeping
        $thePast = \Carbon\Carbon::now()->subSeconds(5);
        for ($i = 0; $i < 3; $i++) {
            $log = LogToDB::model();
            $log->message = "Test remove message {$i}";
            $log->channel = 'test';
            $log->level = 100;
            $log->level_name = 'DEBUG';
            $log->unix_time = $thePast->unix();
            $log->datetime = new \Monolog\DateTimeImmutable(true);
            $log->created_at = $thePast->toDateTimeString();
            $log->updated_at = $thePast->toDateTimeString();
            $log->save();
        }

        $this->assertTrue(LogToDB::model()->removeOldestIfMoreThan(2));
        $this->assertEquals(2, LogToDB::model()->count());
        $this->assertTrue(LogToDB::model()->removeOlderThan(date('Y-m-d H:i:s')));
        $this->assertEquals(0, LogToDB::model()->count());

        // Same tests on mongodb
        $thePast = \Carbon\Carbon::now()->subSeconds(5);
        for ($i = 0; $i < 3; $i++) {
            $log = LogToDB::model('mongodb');
            $log->message = "Test remove message {$i}";
            $log->channel = 'test';
            $log->level = 100;
            $log->level_name = 'DEBUG';
            $log->unix_time = $thePast->unix();
            $log->datetime = new \Monolog\DateTimeImmutable(true);
            $log->created_at = $thePast->toDateTimeString();
            $log->updated_at = $thePast->toDateTimeString();
            $log->save();
        }

        $this->assertTrue(LogToDB::model('mongodb')->removeOldestIfMoreThan(2));
        $this->assertEquals(2, LogToDB::model('mongodb')->count());
        $this->assertTrue(LogToDB::model('mongodb')->removeOlderThan(date('Y-m-d H:i:s')));
        $this->assertEquals(0, LogToDB::model('mongodb')->count());

        // Test wrappers for silly spelling
        $thePast = \Carbon\Carbon::now()->subSeconds(5);
        for ($i = 0; $i < 3; $i++) {
            $log = LogToDB::model();
            $log->message = "Test remove message {$i}";
            $log->channel = 'test';
            $log->level = 100;
            $log->level_name = 'DEBUG';
            $log->unix_time = $thePast->unix();
            $log->datetime = new \Monolog\DateTimeImmutable(true);
            $log->created_at = $thePast->toDateTimeString();
            $log->updated_at = $thePast->toDateTimeString();
            $log->save();
        }

        $this->assertTrue(LogToDB::model()->removeOldestIfMoreThen(2));
        $this->assertEquals(2, LogToDB::model()->count());
        $this->assertTrue(LogToDB::model()->removeOlderThen(date('Y-m-d H:i:s')));
        $this->assertEquals(0, LogToDB::model()->count());

        // Same tests on mongodb (silly spelling)
        $thePast = \Carbon\Carbon::now()->subSeconds(5);
        for ($i = 0; $i < 3; $i++) {
            $log = LogToDB::model('mongodb');
            $log->message = "Test remove message {$i}";
            $log->channel = 'test';
            $log->level = 100;
            $log->level_name = 'DEBUG';
            $log->unix_time = $thePast->unix();
            $log->datetime = new \Monolog\DateTimeImmutable(true);
            $log->created_at = $thePast->toDateTimeString();
            $log->updated_at = $thePast->toDateTimeString();
            $log->save();
        }

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
     * Test the DatetimeFixer, which recomputes the stored datetime column from unix_time.
     * Simulates records saved with the broken pre-v5 'Y-m-d H:i:s:ms' datetime_format.
     *
     * @group datetimeFixer
     */
    public function testDatetimeFixer()
    {
        $unixTime = \Carbon\Carbon::now()->subHour()->unix();
        $brokenDatetime = date('Y-m-d H:i:s', $unixTime) . ':' . date('m', $unixTime) . date('s', $unixTime);
        $correctDatetime = date(config('logtodb.datetime_format'), $unixTime);

        LogToDB::model()->newQuery()->insert([
            'message' => 'Record with broken datetime format',
            'channel' => 'test',
            'level' => 100,
            'level_name' => 'DEBUG',
            'unix_time' => $unixTime,
            'datetime' => $brokenDatetime,
            'created_at' => date('Y-m-d H:i:s', $unixTime),
            'updated_at' => date('Y-m-d H:i:s', $unixTime),
        ]);

        LogToDB::model('mongodb')->newQuery()->insert([
            'message' => 'Record with broken datetime format',
            'channel' => 'test',
            'level' => 100,
            'level_name' => 'DEBUG',
            'unix_time' => $unixTime,
            'datetime' => $brokenDatetime,
            'created_at' => date('Y-m-d H:i:s', $unixTime),
            'updated_at' => date('Y-m-d H:i:s', $unixTime),
        ]);

        $this->assertEquals($brokenDatetime, LogToDB::model()->first()->datetime);
        $this->assertEquals($brokenDatetime, LogToDB::model('mongodb')->first()->datetime);

        //Dry-run should report the fix but not change anything.
        $this->artisan('log:fix-datetime', ['--dry-run' => true])->assertExitCode(0);
        $this->assertEquals($brokenDatetime, LogToDB::model()->first()->datetime);
        $this->assertEquals($brokenDatetime, LogToDB::model('mongodb')->first()->datetime);

        $this->artisan('log:fix-datetime')->assertExitCode(0);
        $this->assertEquals($correctDatetime, LogToDB::model()->first()->datetime);
        $this->assertEquals($correctDatetime, LogToDB::model('mongodb')->first()->datetime);

        //Running again should be a no-op.
        $this->artisan('log:fix-datetime')->assertExitCode(0);
        $this->assertEquals($correctDatetime, LogToDB::model()->first()->datetime);
        $this->assertEquals($correctDatetime, LogToDB::model('mongodb')->first()->datetime);
    }

}
