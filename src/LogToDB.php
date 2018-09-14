<?php

namespace danielme85\LaravelLogToDB;
use danielme85\LaravelLogToDB\Jobs\SaveNewLogEvent;
use danielme85\LaravelLogToDB\Models\DBLog;
use danielme85\LaravelLogToDB\Models\DBLogMongoDB;

/**
 * Class LogToDb
 *
 * @package danielme85\LaravelLogToDB
 */
class LogToDB
{
    /**
     * Connection referenced in logging config.
     * @var null
     */
    public $channelConnection;
    /**
     * Store detailed log
     * @var string
     */
    public $detailed;
    /**
     * Max number of allowed rows
     * @var bool
     */
    public $maxRows;
    /**
     * Connection reference in databases config.
     * @var string
     */
    public $connection;
    /**
     * The table in SQL or Collection in noSQL.
     * @var string
     */
    public $collection;

    /**
     * @var mixed
     */
    public $saveWithQueue;

    /**
     * The DB config details
     * @var null
     */
    private $database;

    /**
     * LogToDB constructor.
     *
     * @param null $channelConnection
     * @param null $collection
     * @param null $detailed
     * @param null $maxRows
     */
    function __construct($channelConnection = null, $collection = null, $detailed = null, $maxRows = null)
    {
        //Log default config if present
        $config = config('logtodb');
        if (!empty($config)) {
            if (isset($config['connection'])) {
                $this->connection = $config['connection'];
            }
            if (isset($config['collection'])) {
                $this->collection = $config['collection'];
            }
            if (isset($config['detailed'])) {
                $this->detailed = $config['detailed'];
            }
            if (isset($config['max_rows'])) {
                $this->maxRows = (int)$config['max_rows'];
            }
            if (isset($config['queue_db_saves'])) {
                $this->saveWithQueue = $config['queue_db_saves'];
            }
        }

        //Set config based on specified config from the Log handler
        if (!empty($channelConnection)) {
            $this->channelConnection = $channelConnection;
        }
        if (!empty($collection)) {
            $this->collection = $collection;
        }
        if (!empty($detailed)) {
            $this->detailed = $detailed;
        }
        if (!empty($maxRows)) {
            $this->maxRows = (int)$maxRows;
        }

        //Get the DB connections
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
     * Return a new LogToDB Module instance.
     *
     * @param string|null $channel
     * @param string|null $connection
     * @param string|null $collection
     *
     * @return DBLog|DBLogMongoDB
     */
    public static function model(string $channel = null, string $connection = null, string $collection = null)
    {
        $conn = null;
        $coll = null;

        if (!empty($channel)) {
            $channels = config('logging.channels');
            if (isset($channels[$channel])) {
                if (isset($channels[$channel]['connection']) and !empty($channels[$channel]['connection'])) {
                    $conn = $channels[$channel]['connection'];
                }
                if (isset($channels[$channel]['collection']) and !empty($channels[$channel]['collection'])) {
                    $coll = $channels[$channel]['collection'];
                }
            }
        }
        else {
            if (!empty($connection)) {
                $conn = $connection;
            }
            if (!empty($collection)) {
                $coll = $collection;
            }
        }

        //Return new instance of this model
        $model = new self($conn, $coll);

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

        if (!empty($this->connection)) {
            if (!empty($this->saveWithQueue)) {
                try {
                    if (dispatch(new SaveNewLogEvent($log))->onQueue($this->saveWithQueue)) {
                        if (!empty($this->maxRows)) {
                            $this->removeOldestIfMaxRows();
                        }
                    }
                } catch (\Exception $e) {
                }
            } else {
                try {
                    if ($log->save()) {
                        if (!empty($this->maxRows)) {
                            $this->removeOldestIfMaxRows();
                        }
                    }
                } catch (\Exception $e) {
                }
            }
        }

        return $this;
    }

    /**
     * Delete the oldest record based on unix_time
     *
     * @return bool success
     */
    private function removeOldestIfMaxRows() {
        $model = $this->model();
        $current = $model->count();
        if ($current > $this->maxRows) {
            $oldest = $model->orderBy('unix_time', 'ASC')->first();
            if ($oldest->delete()) {
                return true;
            }
        }

        return false;
    }

}