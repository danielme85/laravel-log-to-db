<?php

namespace danielme85\LaravelLogToDB;

use Monolog\Logger;
use Monolog\Processor\IntrospectionProcessor;

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
        return new Logger($config['name'] ?? 'LogToDB',
            [
                new LogToDbCustomLoggingHandler(
                    $config['connection'] ?? null,
                    $config['collection'] ?? null,
                    $config['detailed'] ?? null,
                    $config['queue'] ?? null,
                    $config['queue_name'] ?? null,
                    $config['queue_connection'] ?? null,
                    $config['level'] ?? null)
            ],
            [
                new IntrospectionProcessor()
            ]);
    }

}