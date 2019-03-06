# Laravel Log-to-DB
[![GitHub](https://img.shields.io/github/license/mashape/apistatus.svg?style=flat-square)](https://github.com/danielme85/laravel-log-to-db)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/danielme85/laravel-log-to-db.svg?style=flat-square)](https://packagist.org/packages/danielme85/laravel-log-to-db)
[![GitHub release](https://img.shields.io/github/release/danielme85/laravel-log-to-db.svg?style=flat-square)](https://packagist.org/packages/danielme85/laravel-log-to-db)
[![GitHub tag](https://img.shields.io/github/tag/danielme85/laravel-log-to-db.svg?style=flat-square)](https://github.com/danielme85/laravel-log-to-db)
[![Travis (.org)](https://img.shields.io/travis/danielme85/laravel-log-to-db.svg?style=flat-square)](https://travis-ci.org/danielme85/laravel-log-to-db)
[![Codecov](https://img.shields.io/codecov/c/github/danielme85/laravel-log-to-db.svg?style=flat-square)](https://codecov.io/gh/danielme85/laravel-log-to-db)

Custom Larvel 5.6+ Log channel handler that can store log events to SQL or MongoDB databases. 
Uses Laravel native logging functionality.

## Installation
```
require danielme85/laravel-log-to-db
```

If you are going to be using SQL database server to store log events you would need to run the migrations first. The MongoDB driver does not require the migration.
```
php artisan migrate
```

## Configuration
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
    'queue' => ''
    'queue_name' => ''
    'queue_connection' => ''
]
```
 * driver = Required to trigger the log driver.
 * via = The Log handler class.
 * level = The minimum error level to trigger this Log Channel.
 * name = The channel name that will be stored with the Log event.
 * connection = The DB connection from config/database.php to use (default: 'default').
 * collection = The DB table or collection name. (Default: log).
 * detailed = Store detailed log on Exceptions like stack-trace (default: true).
 
More info about some of these options: https://laravel.com/docs/5.6/logging#customizing-monolog-for-channels

There are some default settings and more information about configuring the logger in the 'logtodb.php' config file.
This could be copied to your project if you would like edit it with the vendor publish command.
```
php artisan vendor:publish
```
You can also set log-to-db config settings in your .env file, for ex:
```
LOG_DB_CONNECTION='default'
LOG_DB_DETAILED=false
LOG_DB_MAX=100
LOG_DB_QUEUE=false
LOG_DB_QUEUE_NAME='logToDBQueue'
LOG_DB_QUEUE_CONNECTION='default'

## Usage
Use the default Laravel Facade "Log"
```php
Log::channel()->info("This thing just happened");
Log::channel()->warning("This kind of bad thing happened...");
```
You can give the logging channels whatever name you want instead of: 'database', as well as the log levels.
The naming can be used later if you want to send a Log event to a specific channel:
```php
Log::channel('database')->info("This thing just happened");
Log::channel('mongodb')->info("This thing just happened");
```
This logger works the same as any other across Laravel, for example you can add it to a stack. 
You can log multiple levels to multiple DB connections... the possibilities are ENDLESS! 😎

#### Log Worker Queue
It might be a good idea to save the log events with a Queue Worker. This way your server does not have to wait for
the save process to finish. You would have to configure the Laravel Queue settings and run the Queue listener. 
https://laravel.com/docs/5.6/queues#running-the-queue-worker

## Usage
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
$logs = LogToDB::model()->get();
$logs = LogToDB::model()->where('level_name', '=', 'INFO')->get();
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

##### Custom Model
Since Laravel is supposed to use static defined collection/table names, 
it might be best to use your own model in your app for a more solid approach.
<br>
https://laravel.com/docs/5.7/eloquent#eloquent-model-conventions

```php
namespace App;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'log';
    protected $connection = 'mysq;'
}
```
Fetching the model trough the LogToDB class (like the examples above) might have some side-effects as tables and connections are 
declared dynamically... aka made by Hackermann!
<br>
![](hackerman.gif)


#### Log Cleanup
There is a helper function to remove the oldest log events and keep a specified number
```php
LogToDB::removeOldestIfMoreThen(100);
```
Or based on date (most be valid date/datetime supported by strtotime())
http://php.net/manual/en/function.strtotime.php

```php
LogToDB::removeOlderThen('2019-01-01');
LogToDB::removeOlderThen('2019-01-01 23:00:00');
```

##### Advanced /config/logging.php example
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
        'detailed; => true,
        'queue' => true
        'queue_name' => 'logQueue'
        'queue_connection' => 'redis'
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