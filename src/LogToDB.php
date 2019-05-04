<?php

namespace danielme85\LaravelLogToDB;

use danielme85\LaravelLogToDB\Jobs\SaveNewLogEvent;
use danielme85\LaravelLogToDB\Models\DBLog;
use danielme85\LaravelLogToDB\Models\DBLogMongoDB;
use danielme85\LaravelLogToDB\Models\CreateLogFromRecord;

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
     * @var mixed
     */
    public $saveWithQueueName;

    /**
     * @var mixed
     */
    public $saveWithQueueConnection;

    /**
     * The DB config details
     * @var null
     */
    public $database;

    /**
     * LogToDB constructor.
     *
     * @param string|null $channelConnection
     * @param string|null $collection
     * @param bool|null $detailed
     * @param bool|null $queue
     * @param string|null $queueName
     * @param string|null $queueConnection
     */
    function __construct(string $channelConnection = null,
                         string $collection = null,
                         bool $detailed = null,
                         bool $queue = null,
                         string $queueName = null,
                         string $queueConnection = null)
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
            if (isset($config['queue_db_saves'])) {
                $this->saveWithQueue = $config['queue_db_saves'];
            }
            if (isset($config['queue_db_name'])) {
                $this->saveWithQueueName = $config['queue_db_name'];
            }
            if (isset($config['queue_db_connection'])) {
                $this->saveWithQueueConnection = $config['queue_db_connection'];
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
        if (!empty($queue)) {
            $this->saveWithQueue = $queue;
        }
        if (!empty($queueName)) {
            $this->saveWithQueueName = $queueName;
        }
        if (!empty($queueConnection)) {
            $this->saveWithQueueConnection = $queueConnection;
        }

        //Get the DB connections
        $dbconfig = config('database.connections');

        if (!empty($this->channelConnection)) {
            if (isset($dbconfig[$this->channelConnection])) {
                $this->connection = $this->channelConnection;
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
        if ($this->saveWithQueue) {
            if ($this->saveWithQueueConnection === 'default') {
                $this->saveWithQueueConnection = null;
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
        $model = new self($conn, $coll);

        return $model->getModel();
    }

    /**
     * @return DBLogMongoDB | DBLog;
     */
    private function getModel()
    {
        if ($this->database['driver'] === 'mongodb') {
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
            if ($this->saveWithQueue) {
                if (isset($record['context']['exception']) and !empty($record['context']['exception'])) {
                    if (strpos(get_class($record['context']['exception']), "Exception") !== false) {
                        dispatch_now(new SaveNewLogEvent($this, $record));
                    }
                }
                if (empty($this->saveWithQueueName) and empty($this->saveWithQueueConnection)) {
                    dispatch(new SaveNewLogEvent($this, $record));
                } else if (!empty($this->saveWithQueueName) and !empty($this->saveWithQueueConnection)) {
                    dispatch(new SaveNewLogEvent($this, $record))
                        ->onConnection($this->saveWithQueueConnection)
                        ->onQueue($this->saveWithQueueName);
                } else if (!empty($this->saveWithQueueConnection)) {
                    dispatch(new SaveNewLogEvent($this, $record)
                    )->onConnection($this->saveWithQueueConnection);
                } else if (!empty($this->saveWithQueueName)) {
                    dispatch(new SaveNewLogEvent($this, $record))
                        ->onQueue($this->saveWithQueueName);
                }
             } else {
                $log = CreateLogFromRecord::generate(
                    $this->connection,
                    $this->collection,
                    $record,
                    $this->detailed,
                    $this->database['driver'] ?? null
                );

                if ($log->save()) {

                    return true;
                }
            }
        }

        return false;
    }
}