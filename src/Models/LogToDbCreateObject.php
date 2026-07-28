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
     * Datetime format used by setDatetimeAttribute(). A real declared property (not an
     * Eloquent attribute) so it isn't picked up by save() as a column to persist.
     *
     * @var string|null
     */
    protected $datetimeFormat;

    /**
     * Create a new log object
     *
     * @param \Monolog\LogRecord $record
     * @param string|null $datetimeFormat Overrides config('logtodb.datetime_format'), e.g. with a per-channel format.
     *
     * @return mixed
     */
    public function generate(LogRecord $record, ?string $datetimeFormat = null)
    {
        $this->message = $record->message;
        $this->context = $record->context;
        $this->level = $record->level->value;
        $this->level_name = $record->level->getName();
        $this->channel = $record->channel;
        $this->datetimeFormat = $datetimeFormat ?? config('logtodb.datetime_format');
        $this->datetime = $record->datetime;
        $this->extra = $record->extra;
        $this->unix_time = $record->datetime->getTimestamp();

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
        $this->attributes['datetime'] = $value->format($this->datetimeFormat ?? config('logtodb.datetime_format'));
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
            return json_encode($value) ?: null;
        }

        return null;
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
            //Select only the records to delete (usually far fewer than the keepers) and remove them in chunks.
            $deleted = 0;
            $this->orderBy('unix_time', 'ASC')
                ->take($current - $max)
                ->pluck($this->primaryKey)
                ->chunk(1000)
                ->each(function ($ids) use (&$deleted) {
                    $deleted += $this->whereIn($this->primaryKey, $ids->all())->delete();
                });

            return $deleted > 0;
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
