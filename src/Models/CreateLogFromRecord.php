<?php
/**
 * Created by Daniel Mellum <mellum@gmail.com>
 * Date: 3/1/2019
 * Time: 4:15 PM
 */

namespace danielme85\LaravelLogToDB\Models;

class CreateLogFromRecord
{

    /**
     * Create a new log object
     *
     * @param $log
     * @param array $record
     * @param bool $detailed
     *
     * @return mixed
     */
    public static function generate($log, array $record, bool $detailed = false)
    {
        if (isset($record['message'])) {
            $log->message = $record['message'];
        }
        if ($detailed) {
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

        return $log;
    }
}