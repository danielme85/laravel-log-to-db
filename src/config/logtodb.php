<?php
    /*
    |--------------------------------------------------------------------------
    | Default Config for Laravel Log-To-DB
    |--------------------------------------------------------------------------
    |
    |   These settings are ONLY USED if they are not specified per channel
    |   in the config/logging.php file.
    |
    */
return [
    /*
    |--------------------------------------------------------------------------
    | DB Connection
    |--------------------------------------------------------------------------
    |
    | Set the default database connection to use. This is only used if no connection
    | is specified in config/logging.php. Matches connections in the config/database.php.
    | The default is: 'default', this will use whatever is default in the Laravel DB
    | config file. To use a different or separate connection set the connection name here.
    | Ex: 'connection' => 'mysql' wil use the connection 'mysql' in database.php.
    | Ex: 'connection' => 'mongodb' wil use the connection 'mongodb' in database.php*
    |
    | Supported connections should be same as Laravel since the Laravel DB/Eloquent
    | Supported DB engines as of this writing: [MySQL] [PostgreSQL] [SQLite] [SQL Server]
    |
    | *MongoDB is supported with: "jenssegers/laravel-mongodb".
    | https://github.com/jenssegers/laravel-mongodb
    | laravel-mongodb is required to use the mongodb option for logging.
    */
    'connection' => env('LOG_DB_CONNECTION', ''),

    /*
    |--------------------------------------------------------------------------
    | DB Collection
    |--------------------------------------------------------------------------
    |
    | Set the default database table (sql) or collection (mongodb) to use.
    */
    'collection' => env('LOG_DB_COLLECTION', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Detailed log
    |--------------------------------------------------------------------------
    |
    | Set detailed log. Detailed log means the inclusion of a context (stack trace).
    | This will usually require quite a bit more DB storage space, and is probably
    | only useful in development/debugging. You can still have this enabled in production
    | environments if more detailed error logs are proffered.
    */
    'detailed' => env('LOG_DB_DETAILED', true),

    /*
    |--------------------------------------------------------------------------
    | Model class
    |--------------------------------------------------------------------------
    |
    | You can specify your own custom Eloquent model to be used when saving
    | and getting log events.
    */
    'model' => env('LOG_DB_MODEL', false),

    /*
    |--------------------------------------------------------------------------
    | Enable Queue
    |--------------------------------------------------------------------------
    |
    | It might be a good idea to save log events with the queue helper.
    | This way the requests going to your sever does not have to wait for the Log
    | event to be saved.
    */
    'queue' => env('LOG_DB_QUEUE', false),

    /*
    |--------------------------------------------------------------------------
    | Name of Queue
    |--------------------------------------------------------------------------
    |
    | Set to a string like: 'queue_db_name' => 'logWorker',
    | and make sure to run the queue worker. Leave empty for default queue.
    */
    'queue_name' => env('LOG_DB_QUEUE_NAME', ''),

    /*
    |--------------------------------------------------------------------------
    | Queue Connection
    |--------------------------------------------------------------------------
    |
    | If you are working with multiple queue connections, you may specify which
    | connection to push a job to.
    | This relates yto your queue settings in the config/queue.php file.
    | Leave blank to use the default connection.
    |
    */
    'queue_connection' => env('LOG_DB_QUEUE_CONNECTION', ''),

    /*
    |--------------------------------------------------------------------------
    | Log record purging
    |--------------------------------------------------------------------------
    |
    | Automatically purge db log records based on time and/or max number of records.
    |
    | Number of records: should be an integer that represents the max number of records
    | stored before the oldest is removed.
    | When older than: records older than a give number of hours will be removed.
    |
    */
    'max_records' => env('LOG_DB_MAX_COUNT', false), //Ex: 1000 records

    'max_hours' => env('LOG_DB_MAX_HOURS', false), //Ex: 24 for 24 hours. Or 24*7 = 1 week.

    /*
     |
     | Specify the datetime format storing into the log record
     |
     */
    'datetime_format' => env('LOG_DB_DATETIME_FORMAT', 'Y-m-d H:i:s:ms')

];
