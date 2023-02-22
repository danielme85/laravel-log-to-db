<?php

namespace danielme85\LaravelLogToDB\Facades;

use Illuminate\Support\Facades\Facade;

class LogToDB extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'laravel-log-to-db';
    }

}