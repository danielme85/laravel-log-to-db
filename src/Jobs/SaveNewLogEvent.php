<?php

namespace danielme85\LaravelLogToDB\Jobs;

use danielme85\LaravelLogToDB\LogToDB;
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
     * @param array $config
     * @return void
     */
    public function __construct(protected LogRecord $record, protected array $config = [])
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        (new LogToDB($this->config))->safeWrite($this->record);
    }
}
