<?php

namespace danielme85\LaravelLogToDB\Processors;

use Monolog\Processor\ProcessorInterface;

/**
 * Class PhpVersionProcessor
 * @package danielme85\LaravelLogToDB\Processors
 */
class PhpVersionProcessor implements ProcessorInterface
{
    /**
     * @return array The processed record
     */
    public function __invoke(array $record) {
        $record['extra']['php_version'] = phpversion();

        return $record;
    }
}