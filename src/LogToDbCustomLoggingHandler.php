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
    private $saveWithQueue;
    private $saveWithQueueName;
    private $saveWithQueueConnection;

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

        //Log default config if present
        $defaultConfig = config('logtodb');

        if (!empty($defaultConfig)) {
            if (isset($defaultConfig['connection'])) {
                $this->connection = $defaultConfig['connection'];
            }
            if (isset($defaultConfig['collection'])) {
                $this->collection = $defaultConfig['collection'];
            }
            if (isset($defaultConfig['detailed'])) {
                $this->detailed = $defaultConfig['detailed'];
            }
            if (isset($defaultConfig['queue_db_saves'])) {
                $this->saveWithQueue = $defaultConfig['queue_db_saves'];
            }
            if (isset($defaultConfig['queue_db_name'])) {
                $this->saveWithQueueName = $defaultConfig['queue_db_name'];
            }
            if (isset($defaultConfig['queue_db_connection'])) {
                $this->saveWithQueueConnection = $defaultConfig['queue_db_connection'];
            }
        }


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
        }


        //Set the processors
        if (!empty($processors))
        {
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
                    $this->saveWithQueueConnection);
                $log->newFromMonolog($record);
            } catch (\Exception $e) {

            }
        }
    }
}