<?php

namespace danielme85\LaravelLogToDB\Tests;

use danielme85\LaravelLogToDB\Models\LogToDbCreateObject;
use Illuminate\Database\Eloquent\Model;

/**
 * Class CustomEloquentModel
 *
 * @package danielme85\LaravelLogToDB
 */
class CustomEloquentModel extends Model
{
    use LogToDbCreateObject;

    protected $connection = 'mysql';
    protected $table = 'log';

    protected static function boot()
    {
        parent::boot();

        static::created(function ($log) {
             throw new \ErrorException($log->message);
        });
    }
}