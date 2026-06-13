<?php

namespace danielme85\LaravelLogToDB;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;

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
     * @param bool $bubble Whether the messages that are handled can bubble up the stack or not
     */
    function __construct(array $config,
                         bool $bubble = true)
    {
        $this->config = $config;

        //Set default level debug
        $level = $config['level'] ?? 'debug';

        parent::__construct($level, $bubble);
    }

    /**
     * Write the Log
     *
     * @param \Monolog\LogRecord $record
     */
    protected function write(LogRecord $record): void
    {
        try {
            $log = new LogToDB($this->config);
            $log->newFromMonolog($record);
        } catch (\Throwable $e) {
            LogToDB::emergencyLog(new LogRecord(
                datetime: new \Monolog\DateTimeImmutable(true),
                channel: '',
                level: \Monolog\Level::Critical,
                message: 'There was an error while trying to write the log to a DB, log record pushed to error_log()',
                context: LogToDB::parseIfException(['exception' => $e]),
                extra: []
            ));

            LogToDB::emergencyLog($record);
        }
    }
}
