<?php

use Illuminate\Support\Facades\Log;

class FailureTest extends Tests\TestCase
{
    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        // Override to use a broken default channel (no real DB) to test emergency fallback
        $app['config']->set('logging.default', 'database');
        $app['config']->set('database.connections', []);
    }

    public function testEmergencyFailure()
    {
        $capture = tmpfile();
        $backup = ini_set('error_log', stream_get_meta_data($capture)['uri']);

        Log::info('what?');
        $result = stream_get_contents($capture);

        ini_set('error_log', $backup);

        $this->assertStringContainsString(
            'CRITICAL: There was an error while trying to write the log to a DB',
            $result
        );
        $this->assertStringContainsString(
            'INFO: what?',
            $result
        );
    }
}
