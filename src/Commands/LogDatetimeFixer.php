<?php

namespace danielme85\LaravelLogToDB\Commands;

use danielme85\LaravelLogToDB\LogToDbHandler;
use danielme85\LaravelLogToDB\LogToDB;
use Illuminate\Console\Command;

class LogDatetimeFixer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'log:fix-datetime
                            {--channel= : Only fix records for this specific logging channel}
                            {--chunk=500 : Number of records to update per batch}
                            {--dry-run : Report how many records would be fixed without writing anything}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recompute the stored datetime column from unix_time, fixing records saved with the broken pre-v5 datetime_format (e.g. "Y-m-d H:i:s:ms").';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $chunkSize = (int) $this->option('chunk');
        $dryRun = (bool) $this->option('dry-run');
        $onlyChannel = $this->option('channel');

        $channels = $this->getLogToDbChannels();
        if (empty($channels)) {
            $this->warn('No log-to-db channels found in config/logging.php, nothing to fix.');
            return;
        }

        foreach ($channels as $name => $channel) {
            if (!empty($onlyChannel) && $name !== $onlyChannel) {
                continue;
            }

            $format = $channel['datetime_format'] ?? config('logtodb.datetime_format');
            $model = LogToDB::model($name);
            $fixed = 0;

            $keyName = $model->getKeyName();
            $model->newQuery()->orderBy($keyName)
                ->chunkById($chunkSize, function ($records) use (&$fixed, $format, $dryRun, $model, $keyName) {
                    foreach ($records as $record) {
                        $correct = date($format, $record->getAttribute('unix_time'));
                        if ($record->getAttribute('datetime') !== $correct) {
                            $fixed++;
                            if (!$dryRun) {
                                $model->newQuery()
                                    ->where($keyName, $record->getKey())
                                    ->update(['datetime' => $correct]);
                            }
                        }
                    }
                }, $keyName);

            if ($dryRun) {
                $this->info("Channel '{$name}': {$fixed} record(s) would be fixed.");
            } else {
                $this->info("Channel '{$name}': fixed {$fixed} record(s).");
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
