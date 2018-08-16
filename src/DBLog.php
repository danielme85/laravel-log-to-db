<?php

namespace danielme85\LaravelLogToDB;

use Illuminate\Database\Eloquent\Model;

/**
 * Class DbLog
 *
 * @package danielme85\LaravelLogToDB
 */
class DBLog extends Model
{
    use LogToDbCreateObject;

    public $timestamps = false;
    protected $connection;
    protected $table;

    function __construct($connection = 'mysql', $table = 'log')
    {
        $this->connection = $connection;
        $this->table = $table;
    }
}