<?php

namespace danielme85\LaravelLogToDB\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

/**
 * Class DbLog
 *
 * @package danielme85\LaravelLogToDB
 */
class DBLogMongoDB extends Eloquent
{
    use LogToDbCreateObject;

    protected $connection;
    protected $collection;

    function __construct($connection = 'mongodb', $collection = 'log')
    {
        $this->connection = $connection;
        $this->collection = $collection;
    }
}