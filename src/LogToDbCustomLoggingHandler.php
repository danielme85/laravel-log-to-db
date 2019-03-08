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
    private $saveWithQueue;
    private $saveWithQueueName;
    private $saveWithQueueConnection;

    /**
     * LogToDbHandler constructor.
     *
     * @param string $connection The DB connection.
     * @param string $collection The Collection/Table name for DB.
     * @param bool $detailed Detailed error logging.
     * @param int $maxRows The Maximum number of rows/objects before auto purge.
     * @param bool $enableQueue disable|enable enqueuing log db update
     * @param string $queueName optional name of queue
     * @param string $queueConnection optional name of queue connection
     * @param int $level The minimum logging level at which this handler will be triggered
     * @param bool $bubble Whether the messages that are handled can bubble up the stack or not
     */
    function __construct($connection,
                         $collection,
                         $detailed,
                         $enableQueue,
                         $queueName,
                         $queueConnection,
                         $level = Logger::DEBUG, bool $bubble = true)
    {
        //Log default config if present
        $config = config('logtodb');

        if (!empty($config)) {
            if (isset($config['connection'])) {
                $this->connection = $config['connection'];
            }
            if (isset($config['collection'])) {
                $this->collection = $config['collection'];
            }
            if (isset($config['detailed'])) {
                $this->detailed = $config['detailed'];
            }
            if (isset($config['queue_db_saves'])) {
                $this->saveWithQueue = $config['queue_db_saves'];
            }
            if (isset($config['queue_db_name'])) {
                $this->saveWithQueueName = $config['queue_db_name'];
            }
            if (isset($config['queue_db_connection'])) {
                $this->saveWithQueueConnection = $config['queue_db_connection'];
            }
        }

        //override with config array in logging.php
        if (!empty($connection)) {
            $this->connection = $connection;
        }
        if (!empty($collection)) {
            $this->collection = $collection;
        }
        if (!empty($detailed)) {
            $this->detailed = $detailed;
        }
        if (!empty($collection)) {
            $this->saveWithQueue = $enableQueue;
        }
        if (!empty($enableQueue)) {
            $this->saveWithQueueName = $queueName;
        }
        if (!empty($queueConnection)) {
            $this->saveWithQueueConnection = $queueConnection;
        }

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
                $log = new LogToDB($this->connection,
                    $this->collection,
                    $this->detailed,
                    $this->saveWithQueue,
                    $this->saveWithQueueName,
                    $this->saveWithQueueConnection);
                $log->newFromMonolog($record);
            } catch (\Exception $e) {

            }
        }
    }
}