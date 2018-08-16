<?php

namespace danielme85\LaravelLogToDB;

/**
 * Class LogToDb
 *
 * @package danielme85\LaravelLogToDB
 */
class LogToDb
{
    /**
     * Create a Eloquent Model
     *
     * @param $record
     * @return LogToDb self
     */
    public function newFromMonolog(array $record) : self
    {

        $log = new DbLog();

        if (isset($record['message'])) {
            $log->message = $record['message'];
        }
        /**
         * Storing the error log details takes quite a bit of space in sql database compared to a log file,
         * so this can be disabled in the config.
         */
        if (config('logtodb.detailed')) {
            if (isset($record['context'])) {
                if (!empty($record['context'])) {
                    $log->context = $record['context'];
                }
            }
        }
        if (isset($record['level'])) {
            $log->level = $record['level'];
        }
        if (isset($record['level_name'])) {
            $log->level_name = $record['level_name'];
        }
        if (isset($record['channel'])) {
            $log->channel = $record['channel'];
        }
        if (isset($record['datetime'])) {
            $log->datetime = $record['datetime'];
        }
        if (isset($record['extra'])) {
            if (!empty($record['extra'])) {
                $log->extra = $record['extra'];
            }
        }
        $log->unix_time = time();

        try {
            $log->save();
        } catch (\Exception $e) {
            //Failed saving log
        }


        return $this;
    }

}