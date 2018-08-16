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
        return new Logger('LogToDB',
            [
                new LogToDbCustomLoggingHandler($config['connection'] ?? null, $config['level'] ?? null, $config['collection'] ?? null)
            ],
            [
                new IntrospectionProcessor()
            ]);
    }

}