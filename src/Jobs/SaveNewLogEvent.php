<?php

namespace danielme85\LaravelLogToDB\Jobs;

use danielme85\LaravelLogToDB\Models\DBLog;
use danielme85\LaravelLogToDB\Models\DBLogMongoDB;
use danielme85\LaravelLogToDB\Models\CreateLogFromRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SaveNewLogEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $logToDb;
    protected $record;

    /**
     * Create a new job instance.
     *
     * @param object $logToDb
     * @param  array $record
     * @return void
     */
    public function __construct($logToDb, $record)
    {
        $this->logToDb = $logToDb;
        $this->record = $record;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        if ($this->logToDb->database['driver'] === 'mongodb') {
            //MongoDB has its own Model
            $log = CreateLogFromRecord::generate($this->record,
                new DBLogMongoDB($this->logToDb->connection, $this->logToDb->collection),
                $this->logToDb->detailed
            );
        }
        else {
            //Use the default Laravel Eloquent Model
            $log = CreateLogFromRecord::generate($this->record,
                new DBLog($this->logToDb->connection, $this->logToDb->collection),
                $this->logToDb->detailed
            );
        }

        if ($log->save()) {
            if (!empty($this->logToDb->maxRows)) {
                $this->logToDb->removeOldestIfMaxRows();
            }
        }
    }
}