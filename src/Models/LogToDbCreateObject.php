<?php

namespace danielme85\LaravelLogToDB\Models;

use Monolog\LogRecord;

/**
 * Trait LogToDbCreateObject
 *
 * @package danielme85\LaravelLogToDB
 */
trait LogToDbCreateObject
{
    /**
     * Create a new log object
     *
     * @param \Monolog\LogRecord $record
     *
     * @return mixed
     */
    public function generate(LogRecord $record)
    {
        $this->message = $record->message;
        $this->context = $record->context;
        $this->level = $record->level->value;
        $this->level_name = $record->level->getName();
        $this->channel = $record->channel;
        $this->datetime = $record->datetime;
        $this->extra = $record->extra;
        $this->unix_time = time();

        return $this;
    }

    /**
     * Context Accessor
     *
     * @param $value
     * @return null|array
     */
    public function getContextAttribute($value)
    {
        return $this->jsonDecodeIfNotEmpty($value);
    }

    /**
     * Extra Accessor
     *
     * @param $value
     * @return null|array
     */
    public function getExtraAttribute($value)
    {
        return $this->jsonDecodeIfNotEmpty($value);
    }

    /**
     * DateTime Mutator
     *
     * @param object $value
     */
    public function setDatetimeAttribute(object $value)
    {
        $this->attributes['datetime'] = $value->format(config('logtodb.datetime_format'));
    }

    /**
     * Context Mutator
     *
     * @param array $value
     */
    public function setContextAttribute($value)
    {
        $this->attributes['context'] = $this->jsonEncodeIfNotEmpty($value);
    }

    /**
     * Extra Mutator
     *
     * @param array $value
     */
    public function setExtraAttribute($value)
    {
        $this->attributes['extra'] = $this->jsonEncodeIfNotEmpty($value);
    }

    /**
     * Encode to json if not empty/null
     *
     * @param $value
     * @return string
     */
    private function jsonEncodeIfNotEmpty($value)
    {
        if (!empty($value)) {
            return @json_encode($value) ?? null;
        }
    }

    /**
     * Decode from json if not empty/null
     *
     * @param $value
     * @param bool $arraymode
     * @return mixed
     */
    private function jsonDecodeIfNotEmpty($value, $arraymode = true)
    {
        if (!empty($value)) {
            return json_decode($value, $arraymode);
        }

        return $value;
    }

    /**
     * Delete the oldest records based on unix_time, silly spelling version.
     *
     * @param int $max amount of records to keep
     * @return bool
     */
    public function removeOldestIfMoreThen(int $max)
    {
        return $this->removeOldestIfMoreThan($max);
    }

    /**
     * Delete the oldest records based on unix_time
     *
     * @param int $max amount of records to keep
     * @return bool success
     */
    public function removeOldestIfMoreThan(int $max)
    {
        $current = $this->count();
        if ($current > $max) {
            $keepers = $this->orderBy('unix_time', 'DESC')->take($max)->pluck($this->primaryKey)->toArray();
            if ($this->whereNotIn($this->primaryKey, $keepers)->delete()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Delete records based on date, silly spelling version.
     *
     * @param string $datetime date supported by strtotime: http://php.net/manual/en/function.strtotime.php
     * @return bool success
     */
    public function removeOlderThen(string $datetime)
    {
        return $this->removeOlderThan($datetime);
    }

    /**
     * Delete records based on date.
     *
     * @param string $datetime date supported by strtotime: http://php.net/manual/en/function.strtotime.php
     * @return bool success
     */
    public function removeOlderThan(string $datetime)
    {
        $unixtime = strtotime($datetime);
        $count = $this->where('unix_time', '<=', $unixtime)->delete();

        return $count > 0;
    }
}
