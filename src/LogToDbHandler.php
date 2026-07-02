<?php

namespace danielme85\LaravelLogToDB;

use Monolog\Logger;
use Monolog\Processor\ProcessorInterface;

/**
 * Class LogToDbHandler
 *
 * @package danielme85\LaravelLogToDB
 */
class LogToDbHandler
{
    /**
     * Create a custom Monolog instance.
     *
     * @param  array  $config
     * @return \Monolog\Logger
     */
    public function __invoke(array $config)
    {
        $processors = [];

        if (isset($config['processors']) && !empty($config['processors']) && is_array($config['processors'])) {
           foreach ($config['processors'] as $processorName) {
               if (class_exists($processorName) && is_a($processorName, ProcessorInterface::class, true)) {
                   $processors[] = new $processorName;
               } else {
                   error_log("laravel-log-to-db: configured processor '{$processorName}' was skipped, the class does not exist or does not implement " . ProcessorInterface::class);
               }
           }
        }

        return new Logger($config['name'] ?? 'LogToDB',
            [
                new LogToDbCustomLoggingHandler($config)
            ],
                $processors
            );
    }

}