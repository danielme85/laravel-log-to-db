<?php

namespace danielme85\LaravelLogToDB\Processors;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Class PhpVersionProcessor
 * @package danielme85\LaravelLogToDB\Processors
 */
class PhpVersionProcessor implements ProcessorInterface
{
    /**
     * @return \Monolog\LogRecord The processed record
     */
    public function __invoke(LogRecord $record) {
        $record['extra']['php_version'] = phpversion();

        return $record;
    }
}