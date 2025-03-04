<?php

namespace danielme85\LaravelLogToDB\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Monolog\LogRecord;

class SaveNewLogEvent implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    /**
     * Create a new job instance.
     *
     * @param \Monolog\LogRecord $record
     * @return void
     */
    public function __construct(protected LogRecord $record)
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        app('laravel-log-to-db')->safeWrite($this->record);
    }
}
