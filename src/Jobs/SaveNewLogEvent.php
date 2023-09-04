<?php

namespace danielme85\LaravelLogToDB\Jobs;

use danielme85\LaravelLogToDB\Models\DBLogException;
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
     * @param object $logToDb
     * @param \Monolog\LogRecord $record
     * @return void
     */
    public function __construct(protected object $logToDb, protected LogRecord $record)
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $model = $this->logToDb->getModel();
            $log = $model->generate(
                $this->record,
                $this->logToDb->getConfig('detailed')
            );
            $log->save();
        } catch (\Throwable $e) {
            throw new DBLogException($e->getMessage());
        }
    }
}