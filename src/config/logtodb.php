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
    | is used. See https://laravel.com/docs/5.6/database for more info.
    | Supported DB engines as of this writing: [MySQL] [PostgreSQL] [SQLite] [SQL Server]
    |
    | *MongoDB is supported with: "jenssegers/laravel-mongodb".
    | https://github.com/jenssegers/laravel-mongodb
    | laravel-mongodb is required to use the mongodb option for logging.
    */
    'connection' => 'default',

    /*
    |--------------------------------------------------------------------------
    | DB Collection
    |--------------------------------------------------------------------------
    |
    | Set the default database collection/table to use.
    */
    'collection' => 'log',

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
    'detailed' => true,

    /*
    |--------------------------------------------------------------------------
    | Max number of rows/objects
    |--------------------------------------------------------------------------
    |
    | Set the max number of rows/objects to store in DB. Might be useful to keep
    | DB storage size lower or as a handy automatic way to purge old log events.
    | 'max_rows' => false for no limit.
    */
    'max_rows' => false,

    /*
    |--------------------------------------------------------------------------
    | Name of Queue
    |--------------------------------------------------------------------------
    |
    | It might be a good idea to save log events with the queue helper.
    | This way the requests going to your sever does not have to wait for the Log
    | event to be saved. Set to a string like: 'queue_db_saves' => 'logWorker',
    | and make sure to run the queue worker.
    | https://laravel.com/docs/5.6/queues#running-the-queue-worker
    */
    'queue_db_saves' => false,
];