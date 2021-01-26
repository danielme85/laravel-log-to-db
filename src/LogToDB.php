<?php

namespace danielme85\LaravelLogToDB;

use danielme85\LaravelLogToDB\Jobs\SaveNewLogEvent;
use danielme85\LaravelLogToDB\Models\DBLog;
use danielme85\LaravelLogToDB\Models\DBLogException;
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
        $this->config = $loggingConfig + (config('logtodb') ?? []);
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
    }

    /**
     * Return a new LogToDB Module instance.
     *
     * If specifying 'channel, 'connection' and 'collection' would not be needed (they will be extracted from channel).
     * If specifying 'connection' and 'collection', 'channel' is not needed.
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
            if (!empty($channels[$channel])) {
                if (!empty($channels[$channel]['connection'])) {
                    $conn = $channels[$channel]['connection'];
                }
                if (!empty($channels[$channel]['collection'])) {
                    $coll = $channels[$channel]['collection'];
                }
            }
        } else {
            $conn = $connection;
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
        } else if (isset($this->database['driver']) && $this->database['driver'] === 'mongodb') {
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
        $detailed = $this->getConfig('detailed');

        if (!empty($this->connection)) {
            if ($detailed && !empty($record['context']) && !empty($record['context']['exception'])) {
                $record['context'] = $this->parseIfException($record['context']);
            } else if (!$detailed) {
                $record['context'] = null;
            }
            if ($this->config['queue']) {
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
                    $record
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
    public function getConfig(string $config)
    {
        return $this->config[$config] ?? null;
    }

    /**
     * Parse the exception class
     *
     * @param mixed $context
     * @return array
     */
    private function parseIfException($context)
    {
        if (!empty($context['exception'])) {
            $exception = $context['exception'];
            if (is_object($exception)) {
                if (get_class($exception) === \Exception::class
                    || get_class($exception) === \Throwable::class
                    || is_subclass_of($exception, \Exception::class)
                    || is_subclass_of($exception, \Throwable::class)
                    || strpos(get_class($exception), "Exception") !== false
                    || strpos(get_class($exception), "Throwable") !== false) {

                    $newexception = [];

                    if (method_exists($exception, 'getMessage')) {
                        $newexception['message'] = $exception->getMessage();
                    }
                    if (method_exists($exception, 'getCode')) {
                        $newexception['code'] = $exception->getCode();
                    }
                    if (method_exists($exception, 'getFile')) {
                        $newexception['file'] = $exception->getFile();
                    }
                    if (method_exists($exception, 'getLine')) {
                        $newexception['line'] = $exception->getLine();
                    }
                    if (method_exists($exception, 'getTrace')) {
                        $newexception['trace'] = $exception->getTraceAsString();
                    }
                    if (method_exists($exception, 'getSeverity')) {
                        $newexception['severity'] = $exception->getSeverity();
                    }

                    $context['exception'] = $newexception;
                }
            }
        }

        return $context;
    }

}
