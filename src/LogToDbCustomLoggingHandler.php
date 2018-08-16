<?php

namespace danielme85\LaravelLogToDB;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

/**
 * Class LogToDbHandler
 *
 * @package danielme85\LaravelLogToDB
 */
class LogToDbCustomLoggingHandler extends AbstractProcessingHandler
{
    private $connection;
    private $collection;

    /**
     * LogToDbHandler constructor.
     *
     * @param int $level The minimum logging level at which this handler will be triggered
     * @param bool $bubble Whether the messages that are handled can bubble up the stack or not
     */
    function __construct($connection = 'default', $level = Logger::DEBUG, $collection = 'log', bool $bubble = true)
    {
        $this->connection = $connection;
        $this->collection = $collection;

        parent::__construct($level, $bubble);
    }

    /**
     * Write the Log
     *
     * @param array $record
     */
    protected function write(array $record): void
    {
        if (!empty($record)) {
            try {
                $log = new LogToDB($this->connection, $this->collection);
                $log->newFromMonolog($record);
            } catch (\Exception $e) {
                //
            }
        }
    }
}