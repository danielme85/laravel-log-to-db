# Laravel Log-to-DB
Custom Larvel 5.6+ Log channel handler that can store log events to SQL or MongoDB databases. 
Uses Laravel native logging functionality.

### Installation
```php
require danielme85/laravel-log-to-db
```

### Configuration
Starting with Laravel 5.6 you will have a new settings file: "config/logging.php". 
You will need to add an array under 'channels' for Log-to-DB here like so:
```php
    'database' => [
        'driver' => 'custom',
        'via' => danielme85\LaravelLogToDB\LogToDbHandler::class,
        'level' => env('APP_LOG_LEVEL', 'debug'),
    ]
```
You can give it whatever name you want as the array index instead of: 'database', as well as the log levels.
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

#### Viewing/Getting The Log
The logging by this channel is done trough the Eloquent Model builder. To get access to the underlying models use:
```php
$log = danielme85\LaravelLogToDB\LogToDB::model();
```

Some examples of getting logs
```php
//LogToDB::model() = The Eloquent model;

$logs = LogToDB::model()->all();
$logs = LogToDB::model()->where('id' = $id)->first();

//or

$log = danielme85\LaravelLogToDB\LogToDB::model();
$log->all();
```

Getting log for specific DB connection when using multiple DB storage methods.
```php
$logsFromDatabase = LogToDB::model('mysql')->all();
$logsFromMongoDB = LogToDB::model('mongodb')->all();
```