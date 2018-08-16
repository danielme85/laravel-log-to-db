<?php

namespace danielme85\LaravelLogToDB;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

/**
 * Class DbLog
 *
 * @package danielme85\LaravelLogToDB
 */
class DBLogMongoDB extends Eloquent
{
    use LogToDbCreateObject;

    public $timestamps = false;
    protected $connection;
    protected $collection;

    function __construct($connection = 'mongodb', $collection = 'log')
    {
        $this->connection = $connection;
        $this->collection = $collection;
    }
}