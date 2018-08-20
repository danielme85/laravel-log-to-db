<?php

namespace danielme85\LaravelLogToDB\Jobs;


use danielme85\LaravelLogToDB\Models\DBLog;
use danielme85\LaravelLogToDB\Models\DBLogMongoDB;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SaveNewLogEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $log;

    /**
     * Create a new job instance.
     *
     * @param  DBLog | DBLogMongoDB $log
     * @return void
     */
    public function __construct($log)
    {
        $this->log = $log;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->log->save();
    }
}