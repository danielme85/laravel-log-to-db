# Laravel Log-to-DB
[![Travis (.org)](https://img.shields.io/travis/danielme85/laravel-log-to-db.svg?style=flat-square)](https://travis-ci.org/danielme85/laravel-log-to-db)
[![Codecov](https://img.shields.io/codecov/c/github/danielme85/laravel-log-to-db.svg?style=flat-square)](https://codecov.io/gh/danielme85/laravel-log-to-db)
[![GitHub](https://img.shields.io/github/license/mashape/apistatus.svg?style=flat-square)](https://github.com/danielme85/laravel-log-to-db)

Custom Larvel 5.6+ Log channel handler that can store log events to SQL or MongoDB databases. 
Uses Laravel native logging functionality.

### Installation
```
require danielme85/laravel-log-to-db
```

If you are going to be using SQL database server to store log events you would need to run the migrations first. The MongoDB driver does not require the migration.
```
php artisan migrate
```

### Configuration
Starting with Laravel 5.6 you will have a new settings file: "config/logging.php". 
You will need to add an array under 'channels' for Log-to-DB here like so:
```php
'database' => [
    'driver' => 'custom',
    'via' => danielme85\LaravelLogToDB\LogToDbHandler::class,
    'level' => env('APP_LOG_LEVEL', 'debug'),
    'name' => 'My DB Log',
    'connection' => 'default',
    'collection' => 'log',
    'detailed' => true,
    'max_rows' => false
]
```
 * driver = Required to trigger the log driver.
 * via = The Log handler class.
 * level = The minimum error level to trigger this Log Channel.
 * name = The channel name that will be stored with the Log event.
 * connection = The DB connection from config/database.php to use (default: 'default').
 * collection = The DB table or collection name. (Default: log).
 * detailed = Store detailed log on Exceptions like stack-trace (default: true).
 * max_rows = The number of rows/objects to allow before automatically removing the oldest (default: false).
 
More info about some of these options: https://laravel.com/docs/5.6/logging#customizing-monolog-for-channels

There are some default settings and more information about configuring the logger in the 'logtodb.php' config file.
This can be copied to your project so you can edit it with the vendor publish command.
```
php artisan vendor:publish
```
You can give the logging channels whatever name you want instead of: 'database', as well as the log levels.
The naming can be used later if you want to send a Log event to a specific channel:
```php
Log::channel('database')->info("This thing just happened");
Log::channel('mongodb')->info("This thing just happened");
```
This logger works the same as any other across Laravel, for example you can add it to a stack. 
You can log multiple levels to multiple DB connections... the possibilities are ENDLESS! 😎
```php
'default' => env('LOG_CHANNEL', 'stack'),

'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['database', 'mongodb', 'single'],
    ],
    
    'database' => [
        'driver' => 'custom',
        'via' => danielme85\LaravelLogToDB\LogToDbHandler::class,
        'level' => env('APP_LOG_LEVEL', 'debug'),
        'connection' => 'default',
        'collection' => 'log'
    ],
    
    'mongodb' => [
        'driver' => 'custom',
        'via' => danielme85\LaravelLogToDB\LogToDbHandler::class,
        'level' => env('APP_LOG_LEVEL', 'debug'),
        'connection' => 'mongodb',
        'collection' => 'log'
    ],
    
    'single' => [
        'driver' => 'single',
        'path' => storage_path('logs/laravel.log'),
        'level' => env('APP_LOG_LEVEL', 'debug'),
    ],
    //....
]
```

### Usage
Since this is a custom log channel for Laravel, all "standard" ways of generating log events etc should work with 
the Laravel Log Facade. See https://laravel.com/docs/5.6/logging for more information.

#### Fetching Logs
The logging by this channel is done trough the Eloquent Model builder.
LogToDB::model($channel, $connection, $collection);
You can skip all function variables and the default settings from the config/logtodb.php will be used.
```php
$model = LogToDB::model();
$model->all(); //All logs for defualt channel/connection
```

Some more examples of getting logs
```php
$logs = LogToDB::model()->all();
$logs = LogToDB::model()->where('id' = $id)->first();
```

When getting logs for specific channel or DB connection and collection you can either use the channel name matching 
config/logging.php or connection name from config/databases.php. You can also specify collection/table name if needed as 
the third function variable when fetching the model.  
```php
$logsFromDefault = LogDB::model()->all();
$logsFromChannel = LogDB::model('database')->all();
$logsFromMysql   = LogToDB::model(null, 'mysql')->all();
$logsFromMongoDB = LogToDB::model(null, 'mongodb', 'log')->all();
```