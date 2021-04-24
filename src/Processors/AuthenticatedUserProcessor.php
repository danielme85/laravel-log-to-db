<?php

namespace danielme85\LaravelLogToDB\Processors;

use Illuminate\Support\Facades\Auth;
use Monolog\Processor\ProcessorInterface;

class AuthenticatedUserProcessor implements ProcessorInterface
{
    /**
     * @return array The processed record
     */
    public function __invoke(array $record)
    {
        $record['extra']['user'] = Auth::user();

        return $record;
    }
}
