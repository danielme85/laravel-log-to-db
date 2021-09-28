<?php

namespace danielme85\LaravelLogToDB;

use danielme85\LaravelLogToDB\Models\DBLogException;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;

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
        try {
            $log = new LogToDB($this->config);
            $log->newFromMonolog($record);
        } catch (\Exception $e) {
            $this->emergencyLog([
                'message' => 'There was an error while trying to write the log to a DB, log record pushed to error_log()',
                'level' => Logger::CRITICAL,
                'level_name' => 'critical',
                'context' => LogToDB::parseIfException(['exception' => $e]),
                'extra' => []
            ]);
            $this->emergencyLog($record);
        }
    }

    protected function emergencyLog(array $record)
    {
        $errorHandler = new ErrorLogHandler();
        $errorHandler->setFormatter(new LineFormatter('%level_name%: %message% %context%'));
        $errorHandler->handle($record);
    }
}
