<?php

namespace danielme85\LaravelLogToDB;

/**
 * Trait LogToDbCreateObject
 *
 * @package danielme85\LaravelLogToDB
 */
trait LogToDbCreateObject
{
    public $timestamps = false;
    protected $table = 'log';

    /**
     * Create a Eloquent Model
     *
     * @param $record
     * @return LogToDb self
     */
    public function newFromMonolog(array $record) : self
    {
        if (isset($record['message'])) {
            $this->message = $record['message'];
        }
        /**
         * Storing the error log details takes quite a bit of space in sql database compared to a log file,
         * so this can be disabled in the config.
         */
        if (config('larastats.log.detailed')) {
            if (isset($record['context'])) {
                if (!empty($record['context'])) {
                    $this->context = $record['context'];
                }
            }
        }
        if (isset($record['level'])) {
            $this->level = $record['level'];
        }
        if (isset($record['level_name'])) {
            $this->level_name = $record['level_name'];
        }
        if (isset($record['channel'])) {
            $this->channel = $record['channel'];
        }
        if (isset($record['datetime'])) {
            $this->datetime = $record['datetime'];
        }
        if (isset($record['extra'])) {
            if (!empty($record['extra'])) {
                $this->extra = $record['extra'];
            }
        }
        $this->unix_time = time();

        try {
            $this->save();
        } catch (\Exception $e) {
            //Failed saving log
        }


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
     * DateTime Accessor
     *
     * @param $value
     * @return \DateTime
     */
    public function getDatetimeAttribute($value) : \DateTime
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
                if (strpos(get_class($exception), "\Exception") !== false) {
                    $newexception =
                    [
                        'class' => get_class($exception),
                        'message' => $exception->getMessage(),
                        'code' => $exception->getCode(),
                        'file' => $exception->getFile(),
                        'line' => $exception->getLine(),
                        'trace' => $exception->getTrace(),
                        'previous' => $exception->getPrevious(),
                        'severity' => $exception->getSeverity(),

                    ];
                    $value['exception'] = $newexception;
                }
            }
        }
        $this->attributes['context'] = $this->jsonEncodeIfNotEmpty($value);
    }

    /**
     * DateTime Mutator
     *
     * @param \DateTime  $value
     */
    public function setDatetimeAttribute(\DateTime $value)
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
    private function jsonEncodeIfNotEmpty($value) {
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
    private function jsonDecodeIfNotEmpty($value, $arraymode = true) {
        if (!empty($value)) {
            return json_decode($value, $arraymode);
        }

        return $value;
    }

}