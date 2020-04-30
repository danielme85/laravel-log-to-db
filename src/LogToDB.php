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
     * Connection reference in databases config.
     * @var string
     */
    public $connection;

    /**
     * @var string
     */
    public $collection;

    /**
     * The DB config details
     * @var null
     */
    public $database;

    /**
     * @var string
     */
    protected $model;

    /**
     * @var array
     */
    protected $config;

    /**
     * LogToDB constructor.
     *
     * @param array $loggingConfig config values;.
     */
    function __construct($loggingConfig = [])
    {
        //Log default config if present
        $this->config = $loggingConfig + config('logtodb');
        $this->collection = $this->config['collection'] ?? 'log';
        $this->model = $this->config['model'] ?? null;

        //Get the DB connections
        $dbconfig = config('database.connections');

        if (!empty($this->config['connection'])) {
            if (!empty($dbconfig[$this->config['connection']])) {
                $this->connection = $this->config['connection'];
            }
        }

        //set the actual connection instead of default
        if ($this->connection === 'default' or empty($this->connection)) {
            $this->connection = config('database.default');
        }

        if (isset($dbconfig[$this->connection])) {
            $this->database = $dbconfig[$this->connection];
        }

        if (empty($this->database)) {
            new \ErrorException("Required configs missing: The LogToDB class needs a database correctly setup in the configs: databases.php and logtodb.php");
        }

        //If the string 'default' is set for queue connection, then set null as this defaults to 'default' anyways.
        if (!empty($this->config['queue'])) {
            if ($this->config['queue_name'] === 'default') {
                $this->config['queue_name'] = null;
            }
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
    public static function model(string $channel = null, string $connection = 'default', string $collection = null)
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
        } else {
            if (!empty($connection)) {
                $conn = $connection;
            }
            if (!empty($collection)) {
                $coll = $collection;
            }
        }

        //Return new instance of this model
        $model = new self(['connection' => $conn, 'collection' => $coll]);

        return $model->getModel();
    }

    /**
     * @return DBLogMongoDB | DBLog;
     */
    public function getModel()
    {
        //Use custom model
        if (!empty($this->model)) {
            return new $this->model;
        }
        else if ($this->database['driver'] === 'mongodb') {
            //MongoDB has its own Model
            $mongo = new DBLogMongoDB();
            $mongo->bind($this->connection, $this->collection);

            return $mongo;
        } else {
            //Use the default Laravel Eloquent Model
            $sql = new DBLog();
            $sql->bind($this->connection, $this->collection);

            return $sql;
        }
    }

    /**
     * Create a Eloquent Model
     *
     * @param $record
     * @return bool success
     */
    public function newFromMonolog(array $record)
    {
        if (!empty($this->connection)) {
            if ($this->config['queue']) {
                if (isset($record['context']['exception']) && !empty($record['context']['exception'])) {
                    if (strpos(get_class($record['context']['exception']), "Exception") !== false) {
                        dispatch_now(new SaveNewLogEvent($this, $record));
                    }
                }
                if (empty($this->config['queue_name']) && empty($this->config['queue_connection'])) {
                    dispatch(new SaveNewLogEvent($this, $record));
                } else if (!empty($this->config['queue_name']) && !empty($this->config['queue_connection'])) {
                    dispatch(new SaveNewLogEvent($this, $record))
                        ->onConnection($this->config['queue_connection'])
                        ->onQueue($this->config['queue_name']);
                } else if (!empty($this->config['queue_connection'])) {
                    dispatch(new SaveNewLogEvent($this, $record))
                        ->onConnection($this->config['queue_connection']);
                } else if (!empty($this->config['queue_name'])) {
                    dispatch(new SaveNewLogEvent($this, $record))
                        ->onQueue($this->config['queue_name']);
                }
            } else {
                $model = $this->getModel();
                $log = $model->generate(
                    $record,
                    $this->getConfig('detailed')
                );
                if ($log->save()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get config value
     *
     * @param string $config
     * @return mixed|null
     */
    public function getConfig(string $config) {
        return $this->config[$config] ?? null;
    }
}