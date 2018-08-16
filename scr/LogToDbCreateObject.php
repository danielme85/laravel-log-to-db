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