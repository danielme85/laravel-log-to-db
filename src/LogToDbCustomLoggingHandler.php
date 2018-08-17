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
    private $detailed;
    private $maxRows;

    /**
     * LogToDbHandler constructor.
     *
     * @param string $connection The DB connection.
     * @param string $collection The Collection/Table name for DB.
     * @param bool $detailed Detailed error logging.
     * @param int $maxRows The Maximum number of rows/objects before auto purge.
     * @param int $level The minimum logging level at which this handler will be triggered
     * @param bool $bubble Whether the messages that are handled can bubble up the stack or not
     */
    function __construct($connection, $collection, $detailed, $maxRows, $level = Logger::DEBUG, bool $bubble = true)
    {
        $this->connection = $connection;
        $this->collection = $collection;
        $this->detailed = $detailed;
        $this->maxRows = $maxRows;

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
                $log = new LogToDB($this->connection, $this->collection, $this->detailed, $this->maxRows);
                $log->newFromMonolog($record);
            } catch (\Exception $e) {
                //
            }
        }
    }
}