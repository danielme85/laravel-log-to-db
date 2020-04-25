# Laravel Log-to-DB
[![GitHub](https://img.shields.io/github/license/mashape/apistatus.svg?style=flat-square)](https://github.com/danielme85/laravel-log-to-db)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/danielme85/laravel-log-to-db.svg?style=flat-square)](https://packagist.org/packages/danielme85/laravel-log-to-db)
[![GitHub release](https://img.shields.io/github/release/danielme85/laravel-log-to-db.svg?style=flat-square)](https://packagist.org/packages/danielme85/laravel-log-to-db)
[![GitHub tag](https://img.shields.io/github/tag/danielme85/laravel-log-to-db.svg?style=flat-square)](https://github.com/danielme85/laravel-log-to-db)
[![Travis (.org)](https://img.shields.io/travis/danielme85/laravel-log-to-db.svg?style=flat-square)](https://travis-ci.org/danielme85/laravel-log-to-db)
[![Codecov](https://img.shields.io/codecov/c/github/danielme85/laravel-log-to-db.svg?style=flat-square)](https://codecov.io/gh/danielme85/laravel-log-to-db)
[![CodeFactor](https://www.codefactor.io/repository/github/danielme85/laravel-log-to-db/badge)](https://www.codefactor.io/repository/github/danielme85/laravel-log-to-db)

Custom Laravel 6.x and  5.6+ Log channel handler that can store log events to SQL or MongoDB databases. 
Uses Laravel native logging functionality.


* [Installation](#installation)
* [Configuration](#configuration)
* [Usage](#usage)
* [Fetching Logs](#fetching-logs)
* [Custom Eloquent Model](#custom-eloquent-model)
* [Log Cleanup](#log-cleanup)
* [Processors](#processors)


## Installation
Use the composer require or add to composer.json. 
```
require danielme85/laravel-log-to-db
```

If you are using SQL database server to store log events you would need to run the migrations first. The MongoDB driver does not require the migration.
```
php artisan migrate
```

## Configuration
Starting with Laravel 5.6 you will have a new settings file: "config/logging.php". 
You will need to add an array under 'channels' for Log-to-DB here like so:
```php
'channels' => [
    'stack' => [
        'name' => 'Log Stack',
        'driver' => 'stack',
        'channels' => ['database', 'file'],
    ],
    'database' => [
        'driver' => 'custom',
        'via' => danielme85\LaravelLogToDB\LogToDbHandler::class,
        //'model' => App\Model\Log::class, //Your own optional custom model
        'level' => env('APP_LOG_LEVEL', 'debug'),
        'name' => 'My DB Log',
        'connection' => 'default',
        'collection' => 'log',
        'detailed' => true,
        'queue' => false,
        'queue_name' => '',
        'queue_connection' => '',
        'max_records' => false,
        'max_hours' => false,
        'processors' => [
              //Monolog\Processor\HostnameProcessor::class
              // ..
         ]
    ],
    ...
]
```
 * driver = Required to trigger the log driver.
 * via = The Log handler class.
 * level = The minimum error level to trigger this Log Channel.
 * name = The channel name that will be stored with the Log event. Please note that if you use the stack driver the name value in the stack array is used.
 * connection = The DB connection from config/database.php to use (default: 'default').
 * collection = The DB table or collection name. (Default: log).
 * detailed = Store detailed log on Exceptions like stack-trace (default: true).
 * processors = Array of additional processors. These will add additional info into the 'extra' field in the logged data.
[More information about processors](#processors)
 
More info about some of these options: https://laravel.com/docs/6.x/logging#customizing-monolog-for-channels

There are some default settings and more information about configuring the logger in the 'logtodb.php' config file.
This could be copied to your project if you would like edit it with the vendor publish command.
```
php artisan vendor:publish --provider="danielme85\LaravelLogToDB\ServiceProvider"
```
You can also change these settings in your env file.
```
LOG_DB_CONNECTION='default'
LOG_DB_DETAILED=false
LOG_DB_MAX=100
LOG_DB_QUEUE=false
LOG_DB_QUEUE_NAME='logToDBQueue'
LOG_DB_QUEUE_CONNECTION='default'
LOG_DB_MAX_COUNT=false
LOG_DB_MAX_HOURS=false
LOG_DB_DATETIME_FORMAT='Y-m-d H:i:s:ms'
```

> **PLEASE NOTE**: Starting with v2.2.0, the datetime column will be saved as a string in the format given in
> 'datetime_format' in logtodb.php config file, or the LOG_DB_DATETIME_FORMAT value in your .env file.

#### Config priority order
There are three places you can change different options when using log-to-db: 
1. The config file: config/logtodb.php (after doing vendor:publish).
2. Your .env file will override settings in the logtodb.php config file.
3. The Laravel logging config file: config/logging.php. You need to add a custom array here as mentioned above, 
in this same array you can specify/override config settings specifically for that log channel.

Config values set in point 1 & 2 would work as default for all new log channels you add in the "channels" array for the 
Laravel logging configuration (config/logging.php).

#### Log Worker Queue
It might be a good idea to save the log events with a Queue Worker. This way your server does not have to wait for
the save process to finish. You would have to configure the Laravel Queue settings and run the Queue listener. 
https://laravel.com/docs/6.x/queues#running-the-queue-worker

The queue can be enabled/disabled in any of the following places:  
* LOG_DB_QUEUE = true | in .env
* queue_db_saves => true | in config/logtodb.php
* queue => true | in the log channel config array -> config/logging.php

## Usage
Since this is a custom log channel for Laravel, all "standard" ways of generating log events etc should work with 
the Laravel Log Facade. See https://laravel.com/docs/6.x/logging for more information.
```php
Log::debug("This is an test DEBUG log event");
Log::info("This is an test INFO log event");
Log::notice("This is an test NOTICE log event");
Log::warning("This is an test WARNING log event");
Log::error("This is an test ERROR log event");
Log::critical("This is an test CRITICAL log event");
Log::alert("This is an test ALERT log event");
Log::emergency("This is an test EMERGENCY log event");
```
You can also log to specific log channels:
Log::channel('database')debug("This is an test DEBUG log event");


## Fetching Logs
The logging by this channel is done trough the Eloquent Model builder.
LogToDB::model($channel, $connection, $collection);
You can skip all function variables and the default settings from the config/logtodb.php will be used.
```php
$model = LogToDB::model();
$model->get(); //All logs for default channel/connection
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
$logsFromDefault = LogDB::model()->get(); //Get the logs from the default log channel and default connection.
$logsFromChannel = LogDB::model('database')->get(); //Get logs from the 'database' log channel.
$logsFromChannel = LogDB::model('customname')->get(); //Get logs from the 'customname' log channel.
$logsFromMysql   = LogToDB::model(null, 'mysql')->get(); //Get all logs from the mysql connection (from Laravel database config)
$logsFromMongoDB = LogToDB::model(null, 'mongodb')->get(); //Get all logs from the mongodb connection (from Laravel database config)
```

## Custom Eloquent Model
Since Laravel is supposed to use static defined collection/table names, 
it might be better to use your own model in your app for a more solid approach.
You can use your own eloquent model by referencing it in the config, then adding the trait: "LogToDbCreateObject"

##### SQL
```php
namespace App\Models;

use danielme85\LaravelLogToDB\Models\LogToDbCreateObject;
use Illuminate\Database\Eloquent\Model;

class CustomLog extends Model
{
    use LogToDbCreateObject;

    protected $table = 'log';
    protected $connection = 'mysql';
    
}
```

##### MongoDB
```php
namespace App\Models;
use danielme85\LaravelLogToDB\Models\LogToDbCreateObject;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class CustomLogMongo extends Eloquent
{
    use LogToDbCreateObject;

    protected $collection = 'log';
    protected $connection = 'mongodb';

}
``` 

LOG_DB_MODEL='App\Models\CustomLog'

> **WARNING**: Fetching the model trough the dynamic Eloquent model (default behavior) have some side-effects as tables and connections are 
              declared dynamically instead of assigned properties in the model class. Certain functions are broken like LogToDB::model->all(), while LogToDB::model->where()->get() will work as normal.
>             Using your own models avoids these problems.

#### Model Closures and Observers
You can either add [closures](https://laravel.com/docs/7.x/eloquent#events-using-closures) on your custom application model mentioned above, 
or add a [model observer](https://laravel.com/docs/7.x/eloquent#observers) for the default LogToDb models.
<br>
Create a observer:
```
<?php
namespace App\Observers;

use danielme85\LaravelLogToDB\Models\DBLog;

class LogObserver
{
    public function created(DBLog $log)
    {
        //
    }
}
```
Then add to your AppServiceProvider (or another provider that calls the app boot function).
```
   namespace App\Providers;
   
   use App\Observers\LogObserver;
   use danielme85\LaravelLogToDB\LogToDB;
   use Illuminate\Support\ServiceProvider;
   
   class AppServiceProvider extends ServiceProvider
   {
       public function boot()
       {
           LogToDB::model()->observe(LogObserver::class);
       }
   }
``
 

#### Adding tables/expanding collections 
The Log handler for SQL expects the following schema:
```php
Schema::create('log', function (Blueprint $table) {
      $table->increments('id');
      $table->text('message')->nullable();
      $table->string('channel')->nullable();
      $table->integer('level')->default(0);
      $table->string('level_name', 20);
      $table->integer('unix_time');
      $table->text('datetime')->nullable();
      $table->longText('context')->nullable();
      $table->text('extra')->nullable();
      $table->timestamps();
 });
```
This is the migration that ships with this plugin. You can add as many tables as you want, and reference them in the 'collection' config value. 
Collection = table, I used the term collection as it works for both SQL/noSQL. 
No migrations needed for MongoDB.

No indexes are added per default, so if you fetch a lot of log results based on specific time ranges or types: it might be a good idea to add some indexes. 
 
## Log Cleanup
There are config values that you can set to specify the max number of log records to keep, or the max record age in hours.

* logging.php channel array -> (max_records, max_hours).
* .env file -> (LOG_DB_MAX_COUNT, LOG_DB_MAX_HOURS).

<b>These option is set to *false* per default, these have to be set to desired integers before you can run the "log:delete" artisan command.</b> 
```
php artisan log:delete
```
This command will delete records based on settings described above. Add this command to your Console/kernel.php, or run manually in cron etc to enable automatic cleanup.

#### Manual Cleanup
There is a helper function to remove the oldest log events and keep a specified number
```php
LogToDB::removeOldestIfMoreThan(100);
```
Or based on date (most be valid date/datetime supported by strtotime())
http://php.net/manual/en/function.strtotime.php

```php
LogToDB::model()->removeOlderThan('2019-01-01');
LogToDB::model()->removeOlderThan('2019-01-01 23:00:00');
```

## Processors
Monolog ships with a set of [processors](https://github.com/Seldaek/monolog/tree/master/src/Monolog/Processor), these will generate additional data and populate the 'extra' field.

You could also create your own custom processor, make sure they implement [Monolog\Processor\ProcessorInterface](https://github.com/Seldaek/monolog/blob/master/src/Monolog/Processor/ProcessorInterface.php).

##### Example of custom processor
```php
<?php

namespace App\CustomProcessors;

use Monolog\Processor\ProcessorInterface;

class PhpVersionProcessor implements ProcessorInterface {
     /**
     * @return array The processed record
     */
     public function __invoke(array $record) {
         $record['extra']['php_version'] = phpversion();
         
         return $record;
     }
}

```

## More logging.php config examples
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
        'detailed' => true,
        'queue' => true
        'queue_name' => 'logQueue'
        'queue_connection' => 'redis',
        'max_records' => 1000,
        'max_hours' => 24,
    ],
    
    'mongodb' => [
        'driver' => 'custom',
        'via' => danielme85\LaravelLogToDB\LogToDbHandler::class,
        'level' => 'debug',
        'connection' => 'mongodb',
        'collection' => 'log',
        'detailed' => true,
        'queue' => true
        'queue_name' => 'logQueue'
        'queue_connection' => 'redis'
    ],
    
    'limited' => [
        'driver' => 'custom',
        'via' => danielme85\LaravelLogToDB\LogToDbHandler::class,
        'level' => 'warning',
        'detailed' => false,
        'max_rows' => 10,
        'name' => 'limited',
    ]
    
    'single' => [
        'driver' => 'single',
        'path' => storage_path('logs/laravel.log'),
        'level' => env('APP_LOG_LEVEL', 'debug'),
    ],
    //....
]
```

Development supported by:
<br>
![](hackerman.gif)