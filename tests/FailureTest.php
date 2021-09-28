<?php
/**
 * Created by Daniel Mellum <mellum@gmail.com>
 * Date: 9/28/2021
 * Time: 4:29 AM
 */

use Illuminate\Support\Facades\Log;

class FailureTest extends Orchestra\Testbench\TestCase
{

    protected function getEnvironmentSetUp($app)
    {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'/../', '.env.testing');
        $dotenv->load();

        $app['config']->set('logging.default', 'database');
        $app['config']->set('logging.channels', [
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
        ]);

        $app['config']->set('logtodb', include __DIR__.'/../src/config/logtodb.php');
    }




    public function testEmergencyFailure()
    {
        $capture = tmpfile();
        $backup = ini_set('error_log', stream_get_meta_data($capture)['uri']);

        Log::info('what?');
        $result = stream_get_contents($capture);

        ini_set('error_log', $backup);

        $this->assertStringContainsString(
            'critical: There was an error while trying to write the log to a DB',
            $result
        );
        $this->assertStringContainsString(
            'INFO: what?',
            $result
        );
    }

}