<?php

namespace danielme85\LaravelLogToDB;

/**
 * Class LogToDb
 *
 * @package danielme85\LaravelLogToDB
 */
class LogToDB
{
    public $channelConnection;

    public $detailed = false;

    public $maxRows = false;

    //DB Connection to use
    public $connection = 'default';

    public $collection;

    private $database = null;

    /**
     * LogToDB constructor.
     */
    function __construct($channelConnection = null, $collection = 'log')
    {
        $this->channelConnection = $channelConnection;
        $this->collection = $collection;

        //Set config data
        $config = config('logtodb');
        if (!empty($config)) {
            if (isset($config['connection'])) {
                $this->connection = $config['connection'];
            }
            if (isset($config['detailed'])) {
                $this->detailed = $config['detailed'];
            }
            if (isset($config['max_rows'])) {
                $this->maxRows = $config['max_rows'];
            }
        }

        $dbconfig = config('database.connections');

        if (!empty($this->channelConnection)) {
            if (isset($dbconfig[$this->channelConnection])) {
                $this->connection = $this->channelConnection;
            }
        }

        //set the actual connection instead of default
        if ($this->connection === 'default') {
            $this->connection = config('database.default');
        }

        if (isset($dbconfig[$this->connection])) {
            $this->database = $dbconfig[$this->connection];
        }

        if (empty($this->database)) {
            new \ErrorException("Required configs missing: The LogToDB class needs a database correctly setup in the configs: databases.php and logtodb.php");
        }
    }

    /**
     * @return string
     */
    public static function model($channelConnection = null) {
        $model = new self($channelConnection);
        return $model->getModel();
    }

    /**
     * @return DBLogMongoDB | DBLog;
     */
    private function getModel() {
        if ($this->database['driver'] === 'mongodb') {
            //MongoDB has its own Model
            return new DBLogMongoDB($this->connection, $this->collection);
        }
        else {
            //Use the default Laravel Eloquent Model
            return new DBLog($this->connection, $this->collection);
        }
    }

    /**
     * Create a Eloquent Model
     *
     * @param $record
     * @return LogToDb self
     */
    public function newFromMonolog(array $record) : self
    {

        if ($this->database['driver'] === 'mongodb') {
            //MongoDB has its own Model
            $log = new DBLogMongoDB($this->connection, $this->collection);
        }
        else {
            //Use the default Laravel Eloquent Model
            $log = new DBLog($this->connection, $this->collection);
        }

        if (isset($record['message'])) {
            $log->message = $record['message'];
        }
        /**
         * Storing the error log details takes quite a bit of space in sql database compared to a log file,
         * so this can be disabled in the config.
         */
        if ($this->detailed) {
            if (isset($record['context'])) {
                if (!empty($record['context'])) {
                    $log->context = $record['context'];
                }
            }
        }
        if (isset($record['level'])) {
            $log->level = $record['level'];
        }
        if (isset($record['level_name'])) {
            $log->level_name = $record['level_name'];
        }
        if (isset($record['channel'])) {
            $log->channel = $record['channel'];
        }
        if (isset($record['datetime'])) {
            $log->datetime = $record['datetime'];
        }
        if (isset($record['extra'])) {
            if (!empty($record['extra'])) {
                $log->extra = $record['extra'];
            }
        }
        $log->unix_time = time();

        $log->save();

        return $this;
    }

}