<?php

namespace danielme85\LaravelLogToDB\Models;

/**
 * Trait LogToDbCreateObject
 *
 * @package danielme85\LaravelLogToDB
 */
trait LogToDbCreateObject
{
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
     * DateTime Accessor
     *
     * @param $value
     * @return object
     */
    public function getDatetimeAttribute($value)
    {
        return unserialize($value);
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
     * Context Mutator
     *
     * @param array $value
     */
    public function setContextAttribute(array $value)
    {
        if (isset($value['exception'])) {
            if (!empty($value['exception'])) {
                $exception = $value['exception'];
                if (strpos(get_class($exception), "Exception") !== false) {
                    $newexception = [];
                    $newexception['class'] = get_class($exception);
                    if (method_exists($exception, 'getMessage')) {
                        $newexception['message'] = $exception->getMessage();
                    }
                    else {
                        $newexception['message'] = '';
                    }
                    if (method_exists($exception, 'getCode')) {
                        $newexception['code'] = $exception->getCode();
                    }
                    else {
                        $newexception['code'] = '';
                    }
                    if (method_exists($exception, 'getFile')) {
                        $newexception['file'] = $exception->getFile();
                    }
                    else {
                        $newexception['file'] = '';
                    }
                    if (method_exists($exception, 'getLine')) {
                        $newexception['line'] = $exception->getLine();
                    }
                    else {
                        $newexception['line'] = '';
                    }
                    if (method_exists($exception, 'getTrace')) {
                        $newexception['trace'] = $exception->getTrace();
                    }
                    else {
                        $newexception['trace'] = '';
                    }
                    if (method_exists($exception, 'getPrevious')) {
                        $newexception['previous'] = $exception->getPrevious();
                    }
                    else {
                        $newexception['previous'] = '';
                    }
                    if (method_exists($exception, 'getSeverity')) {
                        $newexception['severity'] = $exception->getSeverity();
                    }
                    else {
                        $newexception['severity'] = '';
                    }

                    $value['exception'] = $newexception;
                }
            }
        }
        $this->attributes['context'] = $this->jsonEncodeIfNotEmpty($value);
    }

    /**
     * DateTime Mutator
     *
     * @param object $value
     */
    public function setDatetimeAttribute(object $value)
    {
        $this->attributes['datetime'] = serialize($value);
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
            return json_encode($value);
        }

        return $value;
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
     * Delete the oldest records based on unix_time
     *
     * @param int $max
     * @return bool success
     */
    public function removeOldestIfMoreThen(int $max)
    {
        $current = $this->count();
        if ($current > $max) {
            $keepers = $this->orderBy('unix_time', 'DESC')->take($max)->pluck('id')->toArray();
            if ($this->whereNotIn('id', $keepers)->get()->each->delete()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Delete records based on date.
     *
     * @param string $datetime date supported by strtotime: http://php.net/manual/en/function.strtotime.php
     * @return bool success
     */
    public function removeOlderThen(string $datetime)
    {
        $unixtime = strtotime($datetime);

        $keepers = $this->where('unix_time', '>=', $unixtime)->pluck('id')->toArray();
        $deletes = $this->whereNotIn('id', $keepers)->get();
        if (!$deletes->isEmpty()){
            if ($deletes->each->delete()) {
                return true;
            }
        }

        return false;
    }

}