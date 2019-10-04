<?php
/**
 * Created by PhpStorm.
 * User: dmellum
 * Date: 10/4/19
 * Time: 1:53 PM
 *  ___       _               _           _               ___
 * |_ _|_ __ | |_ ___ _ __ __| | ___  ___(_) __ _ _ __   |_ _|_ __   ___
 *  | || '_ \| __/ _ \ '__/ _` |/ _ \/ __| |/ _` | '_ \   | || '_ \ / __|
 *  | || | | | ||  __/ | | (_| |  __/\__ \ | (_| | | | |  | || | | | (__
 * |___|_| |_|\__\___|_|  \__,_|\___||___/_|\__, |_| |_| |___|_| |_|\___|
 *                                          |___/
 */

namespace danielme85\LaravelLogToDB\Processors;

use Monolog\Processor\ProcessorInterface;

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