<?php
    /*
    |--------------------------------------------------------------------------
    | Laravel Log To DB
    |--------------------------------------------------------------------------
    |
    | The Laravel Log To DB driver/handler relies on a custom Laravel Log Driver
    | that writes logs to a DB table.
    | A detailed error log with the stack trace and all takes a little more space
    | when stored in a database compared to a txt-based log file.
    | There are a couple of ways to reduce the storage needs:
    |   * Detailed context of the stack trace during an Exception/Error Log event
    |     takes up a lot more space then just the basic log info.
    |     set 'detailed' => false to only get the Log event and not the stack trace.
    |   * Since this logger stores events in DB instead of a txt-file you can
    |     specify a max number of rows to be stored. The oldest log event/row wil be
    |     removed when the limit is hit. Set 'max-rows' => 1000, for no limit set
    |     'max_rows' => false.
    */
return [
    /*
    |--------------------------------------------------------------------------
    | DB Connection
    |--------------------------------------------------------------------------
    |
    | Set database connection to use. Matches connections in the config/database.php.
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
    |
    */
    'connection' => 'default',

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
];