<?php

namespace danielme85\LaravelLogToDB;

use danielme85\LaravelLogToDB\Models\DBLogException;
use Monolog\Handler\AbstractProcessingHandler;

/**
 * Class LogToDbHandler
 *
 * @package danielme85\LaravelLogToDB
 */
class LogToDbCustomLoggingHandler extends AbstractProcessingHandler
{
    /**
     * Logging configuration
     *
     * @var array
     */
    private $config;

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

        $this->config = $config;

        //Set default level debug
        $level = $config['level'] ?? 'debug';

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
     * @throws DBLogException
     */
    protected function write(array $record): void
    {
        if (!empty($record)) {
            if (!empty($record['context']['exception']) && is_object($record['context']['exception']) &&
                get_class($record['context']['exception']) === DBLogException::class) {
                //Do nothing if empty log record or an error Exception from itself.
            } else {
                try {
                    $log = new LogToDB($this->config);
                    $log->newFromMonolog($record);
                } catch (DBLogException $e) {
                    //do nothing if exception of self
                } catch (\Throwable $e) {
                    //convert any runtime Exception while logging to a special class so we can avoid our own
                    //exceptions for 99% less infinite loops!
                    throw new DBLogException($e->getMessage());
                }
            }
        }
    }
}
