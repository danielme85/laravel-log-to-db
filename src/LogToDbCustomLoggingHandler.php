<?php

namespace danielme85\LaravelLogToDB;

use Monolog\Handler\AbstractProcessingHandler;

/**
 * Class LogToDbHandler
 *
 * @package danielme85\LaravelLogToDB
 */
class LogToDbCustomLoggingHandler extends AbstractProcessingHandler
{
    private $connection, $collection, $detailed, $saveWithQueue, $saveWithQueueName, $saveWithQueueConnection,
    $maxCount, $maxDays;

    /**
     * LogToDbHandler constructor.
     *
     * @param array $config Logging configuration from logging.php
     * @param array $processors collection of log processors
     * @param bool $bubble Whether the messages that are handled can bubble up the stack or not
     */
    function __construct(array $config,
                         array $processors,
                         bool $bubble = true)
    {
        //Set default level debug
        $level = 'debug';

        //Override default config with array in logging.php
        if (!empty($config)) {
            if (isset($config['level'])) {
                $level = $config['level'];
            }
            if (isset($config['connection'])) {
                $this->connection = $config['connection'];
            }
            if (isset($config['collection'])) {
                $this->collection = $config['collection'];
            }
            if (isset($config['detailed'])) {
                $this->detailed = $config['detailed'];
            }
            if (isset($config['queue'])) {
                $this->saveWithQueue = $config['queue'];
            }
            if (isset($config['queue_name'])) {
                $this->saveWithQueueName = $config['queue_name'];
            }
            if (isset($config['queue_connection'])) {
                $this->saveWithQueueConnection = $config['queue_connection'];
            }
            if (isset($config['max_records'])) {
                $this->maxCount = $config['max_records'];
            }
            if (isset($config['max_record_hours'])) {
                $this->maxDays = $config['max_record_hours'];
            }
        }

        //Set the processors
        if (!empty($processors)) {
            foreach ($processors as $processor) {
                $this->pushProcessor($processor);
            }
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
                    $this->saveWithQueueConnection,
                    $this->maxCount,
                    $this->maxDays);
                $log->newFromMonolog($record);
            } catch (\Exception $e) {
                //We want ignore this exception for (at least) two reasons:
                //1. Let's not ruin the whole app/request/job whatever is supposed to happen
                // by this log write operation failing.
                //2. There is a potential for an infinate loop of this log writer failing,
                // then trying to write a log about the log failing :(
            }
        }
    }
}