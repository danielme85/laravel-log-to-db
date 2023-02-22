<?php

namespace danielme85\LaravelLogToDB;

use danielme85\LaravelLogToDB\Models\DBLogException;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Handler\ErrorLogHandler;
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
     * @param \Monolog\LogRecord $record
     * @throws DBLogException
     */
    protected function write(LogRecord $record): void
    {
        try {
            $log = new LogToDB($this->config);
            $log->newFromMonolog($record);
        } catch (\Exception $e) {

            $this->emergencyLog(new LogRecord(
                datetime: new \Monolog\DateTimeImmutable(true),
                channel: '',
                level: \Monolog\Level::Critical,
                message: 'There was an error while trying to write the log to a DB, log record pushed to error_log()',
                context: LogToDB::parseIfException(['exception' => $e]),
                extra: []
            ));
            
            $this->emergencyLog($record);
        }
    }

    protected function emergencyLog(LogRecord $record)
    {
        $errorHandler = new ErrorLogHandler();
        $errorHandler->setFormatter(new LineFormatter('%level_name%: %message% %context%'));
        $errorHandler->handle($record);
    }
}
