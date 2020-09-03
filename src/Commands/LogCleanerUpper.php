<?php
/**
 * Created by PhpStorm.
 * User: dmellum
 * Date: 2/2/20
 * Time: 10:38 PM
 */

namespace danielme85\LaravelLogToDB\Commands;

use Carbon\Carbon;
use danielme85\LaravelLogToDB\LogToDbHandler;
use danielme85\LaravelLogToDB\LogToDB;
use Illuminate\Console\Command;

class LogCleanerUpper extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'log:delete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup/delete/prune/trim log records.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     */
    public function handle()
    {
        $config = config('logtodb');
        $channels = $this->getLogToDbChannels();

        if (!empty($channels)) {
            foreach ($channels as $name => $channel) {
                $maxRecords = $channel['max_records'] ?? $config['purge_log_when_max_records']  ?? false;
                $maxHours = $channel['max_hours'] ?? $config['purge_log_when_max_records'] ?? false;

                //delete based on numbers of records
                if (!empty($maxRecords) && $maxRecords > 0) {
                    if (LogToDB::model($name)->removeOldestIfMoreThan($maxRecords)) {
                        $this->warn("Deleted oldest records on log channel: {$name}, keep max number of records: {$maxRecords}");
                    }
                }

                //delete based on age
                if (!empty($maxHours) && $maxHours > 0) {
                    $time = Carbon::now()->subHours($maxHours)->toDateTimeString();
                    if (LogToDB::model($name)->removeOlderThan($time)) {
                        $this->warn("Deleted oldest records on log channel: {$name}, older than: {$time}");
                    }
                }
            }
        }
    }

    /**
     * @return array
     */
    private function getLogToDbChannels()
    {
        $list = [];
        $logging = config('logging');
        if (!empty($logging) && isset($logging['channels']) && !empty($logging['channels'])) {
            foreach ($logging['channels'] as $name => $channel) {
                //Only look for the relevant logging class in the config.
                if (isset($channel['via']) && $channel['via'] === LogToDbHandler::class) {
                    $list[$name] = $channel;
                }
            }
        }

        return $list;
    }
}